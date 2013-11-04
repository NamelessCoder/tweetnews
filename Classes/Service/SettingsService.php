<?php
class Tx_Tweetnews_Service_SettingsService extends Tx_News_Service_SettingsService implements t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $overriddenSettings;

	/**
	 * @param $settings
	 * @return void
	 */
	public function setOverriddenSettings($settings) {
		$this->overriddenSettings = $settings;
	}

	/**
	 * Returns all settings.
	 *
	 * @return array
	 */
	public function getSettings() {
		if ($this->overriddenSettings) {
			return $this->overriddenSettings;
		}
		$this->settings = $this->configurationManager->getConfiguration(
			Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
		);
		$this->settings = $this->settings['plugin.']['tx_news.']['settings.'];
		$this->settings = t3lib_div::removeDotsFromTS($this->settings);
		return $this->settings;
	}

}
