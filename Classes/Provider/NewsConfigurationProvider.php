<?php
require_once t3lib_extMgm::extPath('tweetnews', 'Resources/Contrib/CodeBird.php');
class Tx_Tweetnews_Provider_NewsConfigurationProvider extends Tx_Flux_Provider_AbstractConfigurationProvider {

	/**
	 * @var Tx_News_Domain_Repository_NewsRepository
	 */
	protected $newsRepository;

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
		$query->matching($query->equals('uid', $id));
		/** @var $newsItem Tx_News_Domain_Model_News */
		$newsItem = $query->execute()->getFirst();
		$now = time();
		// validity and published status checks
		if ($newsItem->getDatetime()->getTimestamp() > $now) {
			#die('test');
			return;
		}
		if ($newsItem->getHidden()) {
			return;
		}
		if ($newsItem->getDeleted()) {
			return;
		}

		$settings = $this->getSettings('tweetnews');
		$title = $newsItem->getTitle();
		if (strlen($title) <= intval($settings['maximumTitleLength'])) {
			$truncatedTitle = $title;
		} else {
			$truncatedTitle = substr($title, 0, $settings['maximumTitleLength']);
			$truncatedTitle .= $settings['truncatedTitleSuffix'];
		}

		$api = Codebird::getInstance();
		$api->setConsumerKey($settings['consumerKey'], $settings['consumerSecret']);
		$api->setToken($settings['accessToken'], $settings['accessTokenSecret']);
		$timeline = $api->statuses_homeTimeline();
		foreach ($timeline as $tweet) {
			if (0 === strpos($tweet->text, $truncatedTitle)) {
				$this->sendFlashMessage('Already tweeted on ' . $tweet->created_at . ': ' . $tweet->text);
				return;
			}
		}

		$uri = $this->getUriForNewsItem($newsItem);
		$uri = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' . $uri;
		$uri = urlencode($uri);
		$tweet = $truncatedTitle . ($settings['addSpaceBeforeBindingText'] ? ' ' : '') .
			$settings['bindingText'] .
			($settings['addSpaceAfterBindingText'] ? ' ' : '') .
			$uri;
		if ($newsItem->getAuthor() && $settings['displayAuthor'] > 0) {
			$tweet .= $settings['authorBindingText'] . ' ' . $newsItem->getAuthor();
		}

		$response = $api->statuses_update('status=' . $tweet);
		if ($response->httpstatus != 200) {
			throw new Exception('Error while tweeting news item; consult the TYPO3 log for additional details', 1362952088);
		}
		$this->sendFlashMessage('Tweeted: ' . $response->text);
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
		Tx_Extbase_Utility_FrontendSimulator::simulateFrontendEnvironment($this->configurationManager->getContentObject());
		$GLOBALS['TSFE']->sys_page = new t3lib_pageSelect();
		$GLOBALS['TSFE']->tmpl = new t3lib_TStemplate();
		$GLOBALS['TT'] = new t3lib_TimeTrackNull();
		$arguments = array(
			'newsItem' => $newsItem,
			'uriOnly' => TRUE,
		);
		/** @var $settingsService Tx_Tweetnews_Service_SettingsService */
		$settingsService = $this->objectManager->get('Tx_Tweetnews_Service_SettingsService');
		$settings = $this->getSettings('news');
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

		$uri = $viewHelper->render($newsItem, $settings, TRUE, array('absRefPrefix' => 1));
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