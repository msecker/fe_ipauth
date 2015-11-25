<?php

########################################################################
# Extension Manager/Repository config file for ext "fe_ipauth".
#
# Auto generated 07-08-2015 21:05
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
	'version' => '0.2.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users,fe_groups',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Matthias Secker',
	'author_email' => 'secker@alto.de',
	'author_company' => 'alto.de New Media GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2-7.6',
		),
		'conflicts' => array(
			'cc_ipauth' => '0.0.0',
			'cc_iplogin_fe' => '0.0.0',
			'cc_iplogin_be' => '0.0.0'
		),
		'suggests' => array(
		),
	),
);

?>