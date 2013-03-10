<?php
class Tx_Tweetnews_Service_SettingsService extends Tx_News_Service_SettingsService implements t3lib_Singleton {

	/**
	 * Returns all settings.
	 *
	 * @return array
	 */
	public function getSettings() {
		$this->settings = $this->configurationManager->getConfiguration(
			Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
		);
		$this->settings = $this->settings['plugin.']['tx_news.']['settings.'];
		$this->settings = Tx_Flux_Utility_Array::convertTypoScriptArrayToPlainArray($this->settings);
		return $this->settings;
	}

}
