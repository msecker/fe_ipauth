<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}



$_EXTCONF = unserialize($_EXTCONF);    // unserializing the configuration so we can use it here

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['checkRuleFirst_FE_user'] = trim($_EXTCONF['checkRuleFirst_FE_user']) ? trim($_EXTCONF['checkRuleFirst_FE_user']): 'deny';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['allowOverride_FE_user'] = trim($_EXTCONF['allowOverride_FE_user']) ? trim($_EXTCONF['allowOverride_FE_user']): 'no';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['defaultRule_FE_user'] = trim($_EXTCONF['defaultRule_FE_user']) ? trim($_EXTCONF['defaultRule_FE_user']): 'deny';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['checkRuleFirst_FE_group'] = trim($_EXTCONF['checkRuleFirst_FE_group']) ? trim($_EXTCONF['checkRuleFirst_FE_group']): 'deny';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['allowOverride_FE_group'] = trim($_EXTCONF['allowOverride_FE_group']) ? trim($_EXTCONF['allowOverride_FE_group']): 'no';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['defaultRule_FE_group'] = trim($_EXTCONF['defaultRule_FE_group']) ? trim($_EXTCONF['defaultRule_FE_group']): 'deny';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['simulateIP'] = trim($_EXTCONF['simulateIP']);

if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['simulateIP']) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']['fe_ipauth'] = 'EXT:fe_ipauth/Classes/Hook/EarlyHook.php:EarlyHook->simulateIP';
}


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['fe_ipauth'] = 'Alto\\FeIpauth\\Hook\\TceMain';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY,  'auth' /* sv type */,  'Alto\\FeIpauth\\Service\\AuthenticationService' /* sv key */,
		array(
			'title' => 'IP Authentication',
			'description' => 'Authenticates against IP lists',

			'subtype' => 'authUserFE,authGroupsFE',

			'available' => TRUE,
			'priority' => 40,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'Classes/Service/AuthenticationService.php',
			'className' => 'Alto\FeIpauth\Service\AuthenticationService',
		)
	);
	

?>
