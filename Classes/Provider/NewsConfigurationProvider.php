<?php
require_once t3lib_extMgm::extPath('tweetnews', 'Resources/Contrib/CodeBird.php');
class Tx_Tweetnews_Provider_NewsConfigurationProvider extends Tx_Flux_Provider_AbstractConfigurationProvider {

	/**
	 * @var Tx_News_Domain_Repository_NewsRepository
	 */
	protected $newsRepository;

	/**
	 * @var array
	 */
	protected $localSettings = array();

	/**
	 * @var array
	 */
	protected $newsSettings = array();

	/**
	 * @var string
	 */
	protected $tableName = 'tx_news_domain_model_news';

	/**
	 * @param Tx_News_Domain_Repository_NewsRepository $newsRepository
	 * @return void
	 */
	public function injectNewsRepository(Tx_News_Domain_Repository_NewsRepository $newsRepository) {
		$this->newsRepository = $newsRepository;
	}

	public function initializeObject() {
		$this->localSettings = $this->getSettings('tweetnews');
		$this->newsSettings = $this->getSettings('news');
	}

	/**
	 * @param string $operation
	 * @param integer $id
	 * @param array $row
	 * @param t3lib_TCEmain $reference
	 * @throws Exception
	 * @return void
	 */
	public function postProcessRecord($operation, $id, array &$row, t3lib_TCEmain $reference) {
		$query = $this->newsRepository->createQuery();
		$query->getQuerySettings()->setRespectStoragePage(FALSE);
		$query->getQuerySettings()->setIgnoreEnableFields(TRUE);
		$query->matching($query->equals('uid', $id));
		/** @var $newsItem Tx_News_Domain_Model_News */
		$newsItem = $query->execute()->getFirst();
		if (TRUE === empty($newsItem)) {
			$this->sendFlashMessage('News item not yet tweeted - save it once more to trigger tweeting');
			return;
		}

		$localSettings = array(
			'debug', 'consumerKey', 'consumerSecret', 'accessToken', 'accessTokenSecret',
			'maximumTitleLength', 'truncatedTitleSuffix', 'bindingText', 'addSpaceBeforeBindingText',
			'addSpaceAfterBindingText', 'displayAuthor', 'authorBindingText'
		);
		list (
			$debugMode,
			$consumerKey,
			$consumerSecret,
			$accessToken,
			$accessTokenSecret,
			$maximumTitleLength,
			$truncatedTitleSuffix,
			$bindingText,
			$addSpaceBeforeBindingText,
			$addSpaceAfterBindingText,
			$displayAuthor,
			$authorBindingText
			) = $this->getLocalSetting($localSettings);
		$title = $newsItem->getTitle();
		if (strlen($title) <= intval($maximumTitleLength)) {
			$truncatedTitle = $title;
		} else {
			$truncatedTitle = substr($title, 0, $maximumTitleLength);
			$truncatedTitle .= $truncatedTitleSuffix;
		}

		$proceed = $this->consistencyCheck($newsItem);
		if (FALSE === $proceed && !$debugMode) {
			return;
		} elseif ($debugMode) {
			$this->sendFlashMessage('Debug: Proceeding regardless of prevented tweeting.');
		}
		$api = Codebird::getInstance();
		$api->setConsumerKey($consumerKey, $consumerSecret);
		$api->setToken($accessToken, $accessTokenSecret);
		$timeline = $api->statuses_homeTimeline();
		foreach ($timeline as $tweet) {
			if (0 === strpos($tweet->text, $truncatedTitle)) {
				$this->sendFlashMessage('Already tweeted on ' . $tweet->created_at . ': ' . $tweet->text);
				return;
			}
		}

		$uri = $this->getUriForNewsItem($newsItem);
		$uri = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' . $uri;
		if ($debugMode) {
			$fullUri = $uri;
			$linkShortenerOutputLength = 25;
			if ($linkShortenerOutputLength < strlen($uri)) {
				$linkShortenedChars = strlen($uri) - ($linkShortenerOutputLength + 3); // suffixed ellipsis takes another 3 chars.
				$uri = substr($uri, 0, $linkShortenerOutputLength) . '...';
			}
		} else {
			$uri = urlencode($uri);
		}
		$tweet = $truncatedTitle . ($addSpaceBeforeBindingText ? ' ' : '') .
			$bindingText .
			($addSpaceAfterBindingText ? ' ' : '') .
			$uri;
		if ($newsItem->getAuthor() && $displayAuthor > 0) {
			$tweet .= $authorBindingText . ' ' . $newsItem->getAuthor();
		}
		if ($debugMode) {
			$debugText = 'Would tweet: "' . urldecode($tweet);
			$debugText .= '"<br />(' . (strlen($tweet)) . ' chars assuming link shortening to ' .
				$linkShortenerOutputLength . ' chars plus ellipsis suffix)' .
				'<br /> The tweeted URL would be: <a href="' . $fullUri . '">' . $fullUri . '</a>.';
			$this->sendFlashMessage($debugText);
		} else {
			$response = $api->statuses_update('status=' . $tweet);
			if ($response->httpstatus != 200) {
				throw new Exception('Error while tweeting news item; consult the TYPO3 log for additional details', 1362952088);
			}
			$this->sendFlashMessage('Tweeted: "' . $response->text . '"<br />(' . strlen($response->text) . ' chars. Date stamp: ' .
				$response->created_at . ')');
		}
	}

	/**
	 * @param Tx_News_Domain_Model_News $newsItem
	 * @return void
	 */
	protected function consistencyCheck(Tx_News_Domain_Model_News $newsItem) {
		$debugMode = $this->getLocalSetting('debug');
		$verdict = TRUE;
		$now = time();
		// validity and published status checks
		if ($newsItem->getDatetime()->getTimestamp() > $now) {
			$verdict = FALSE;
			if ($debugMode) {
				$this->sendFlashMessage('Debug: Tweet prevented - date is in the future (' . $newsItem->getDatetime()->format('Y-m-d H:i') . ')');
			}
		}
		if ($newsItem->getHidden()) {
			$verdict = FALSE;
			if ($debugMode) {
				$this->sendFlashMessage('Debug: Tweet prevented - news item is hidden');
			}
		}
		if ($newsItem->getDeleted()) {
			$verdict = FALSE;
			if ($debugMode) {
				$this->sendFlashMessage('Debug: Tweet prevented - news item is deleted');
			}
		}
		return $verdict;
	}

	/**
	 * @param mixed $nameOrNamesArray
	 * @return mixed
	 */
	protected function getLocalSetting($nameOrNamesArray) {
		if (TRUE === is_array($nameOrNamesArray)) {
			return array_map(array($this, 'getLocalSetting'), $nameOrNamesArray);
		}
		return $this->localSettings[$nameOrNamesArray];
	}

	/**
	 * @param mixed $nameOrNamesArray
	 * @return mixed
	 */
	protected function getNewsSetting($nameOrNamesArray) {
		if (TRUE === is_array($nameOrNamesArray)) {
			return array_map(array($this, 'getNewsSetting'), $nameOrNamesArray);
		}
		return $this->newsSettings[$nameOrNamesArray];
	}

	/**
	 * @param string $extensionKey
	 * @return array
	 */
	protected function getSettings($extensionKey) {
		$settings = $this->configurationManager->getConfiguration(
			Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
		);
		$settings = (array) $settings['plugin.']['tx_' . $extensionKey . '.']['settings.'];
		$settings = Tx_Flux_Utility_Array::convertTypoScriptArrayToPlainArray($settings);
		return $settings;
	}

	/**
	 * @param Tx_News_Domain_Model_News $newsItem
	 * @return string
	 */
	protected function getUriForNewsItem(Tx_News_Domain_Model_News $newsItem) {
		#Tx_Extbase_Utility_FrontendSimulator::simulateFrontendEnvironment($this->configurationManager->getContentObject());
		/** @var $settingsService Tx_Tweetnews_Service_SettingsService */
		$settingsService = $this->objectManager->get('Tx_Tweetnews_Service_SettingsService');
		$settings = $this->getSettings('news');
		$GLOBALS['TT'] = new t3lib_TimeTrackNull();
		$GLOBALS['TSFE'] = new tslib_fe($GLOBALS['TYPO3_CONF_VARS'], $newsItem->getPid(), 0);
		$GLOBALS['TSFE']->sys_page = new t3lib_pageSelect();
		$GLOBALS['TSFE']->tmpl = new t3lib_TStemplate();
		$rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($newsItem->getPid());
		$GLOBALS['TSFE']->tmpl->start($rootLine);
		$GLOBALS['TSFE']->tmpl->runThroughTemplates($rootLine);
		$settings['defaultDetailPid'] = $settings['detailPid'] = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_news.']['settings.']['defaultDetailPid'];
		$rootLine = $GLOBALS['TSFE']->sys_page->getRootLine($settings['defaultDetailPid']);
		$GLOBALS['TSFE']->tmpl->runThroughTemplates($rootLine);
		$GLOBALS['TSFE']->tmpl->start($rootLine);
		$GLOBALS['TSFE']->config = Tx_Flux_Utility_Array::convertTypoScriptArrayToPlainArray($GLOBALS['TSFE']->tmpl->setup);
		$GLOBALS['TSFE']->config['mainScript'] = 'index.php';
		$localisedSettings = Tx_Flux_Utility_Array::convertTypoScriptArrayToPlainArray($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_news.']['settings.']);
		$localisedSettings['detailPid'] = $localisedSettings['defaultDetailPid'] > 0 ? $localisedSettings['defaultDetailPid'] : $localisedSettings['detailPid'];
		$GLOBALS['TSFE']->id = $localisedSettings['detailPid'];
		$GLOBALS['TSFE']->absRefPrefix = '';
		$settingsService->setOverriddenSettings($localisedSettings);
		$arguments = array(
			'newsItem' => $newsItem,
			'uriOnly' => TRUE,
		);
		/** @var $context Tx_Fluid_Core_Rendering_RenderingContext */
		$context = new Tx_Fluid_Core_Rendering_RenderingContext();
		/** @var $viewHelper Tx_News_ViewHelpers_LinkViewHelper */
		$viewHelper = $this->objectManager->create('Tx_News_ViewHelpers_LinkViewHelper');
		$viewHelper->setArguments($arguments);
		$viewHelper->injectSettingsService($settingsService);
		/** @var $node Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode */
		$node = new Tx_Fluid_Core_Parser_SyntaxTree_ViewHelperNode($viewHelper, $arguments);
		$text = new Tx_Fluid_Core_Parser_SyntaxTree_TextNode($newsItem->getTitle());
		$node->addChildNode($text);
		$viewHelper->setViewHelperNode($node);
		$viewHelper->setRenderingContext($context);
		$viewHelper->setArguments($arguments);
		$uri = $viewHelper->render($newsItem, $localisedSettings, TRUE);
		Tx_Extbase_Utility_FrontendSimulator::resetFrontendEnvironment();
		return $uri;
	}

	/**
	 * @param string $message
	 */
	protected function sendFlashMessage($message) {
		$flashMessage = new t3lib_FlashMessage($message);
		t3lib_FlashMessageQueue::addMessage($flashMessage);
	}

}