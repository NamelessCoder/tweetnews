<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "gits".
 *
 * Auto generated 05-12-2012 21:13
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Tweet News: Automatic tweeting of published EXT:news items',
	'description' => 'Use the Twitter API to tweet about news items added in Georg Ringers EXT:news extension. Minimal configuration - no database storage used.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '0.0.1',
	'dependencies' => 'cms,extbase,fluid,news,flux',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Claus Due',
	'author_email' => 'claus@wildside.dk',
	'author_company' => 'Wildside A/S',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' =>
	array (
		'depends' =>
		array (
			'cms' => '',
			'extbase' => '',
			'fluid' => '',
			'news' => '',
			'flux' => '',
		),
		'conflicts' => array(),
		'suggests' => array (),
	),
);

?>