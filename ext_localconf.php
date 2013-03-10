<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

Tx_Flux_Core::registerConfigurationProvider('Tx_Tweetnews_Provider_NewsConfigurationProvider');
