<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "tweetnews".
 *
 * Auto generated 10-03-2013 23:31
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tweet News: Automatic tweeting of published EXT:news items',
	'description' => 'Use the Twitter API to tweet about news items added in Georg Ringers EXT:news extension. Minimal configuration - no database storage used.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.9.0',
	'dependencies' => 'cms,extbase,fluid,news,flux',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Claus Due',
	'author_email' => 'claus@wildside.dk',
	'author_company' => 'Wildside A/S',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'extbase' => '',
			'fluid' => '',
			'news' => '',
			'flux' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:7:{s:17:"ext_localconf.php";s:4:"253f";s:14:"ext_tables.php";s:4:"5d20";s:9:"README.md";s:4:"1831";s:46:"Classes/Provider/NewsConfigurationProvider.php";s:4:"55c8";s:35:"Classes/Service/SettingsService.php";s:4:"dd68";s:34:"Configuration/TypoScript/setup.txt";s:4:"fe57";s:30:"Resources/Contrib/CodeBird.php";s:4:"6bcb";}',
);

?>
