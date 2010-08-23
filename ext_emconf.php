<?php

########################################################################
# Extension Manager/Repository config file for ext "fe_ipauth".
#
# Auto generated 12-07-2010 12:41
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Auth: FE IP Authentication',
	'description' => 'Allows FE authentication against IP lists.',
	'category' => 'services',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users,fe_groups',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Bernhard Kraft',
	'author_email' => 'kraftb@think-open.at',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.0.0-0.0.0',
			'typo3' => '4.0.0-0.0.0',
			'cms' => '1.0.0-0.0.0',
			'sv' => '1.0.0-0.0.0',
		),
		'conflicts' => array(
			'cc_ipauth' => '',
			'cc_iplogin_fe' => '',
			'cc_iplogin_be' => '',
		),
		'suggests' => array(
			'fe_iplogin' => '',
		),
	),
);

?>
