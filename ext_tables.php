<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$tempColumns = array(
	'tx_feipauth_ip_allow' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/Resources/Private/Language/locallang_db.xlf:fe_users.tx_feipauth_ip_allow',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_feipauth_ip_deny' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/Resources/Private/Language/locallang_db.xlf:fe_users.tx_feipauth_ip_deny',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
);



\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users',$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users','tx_feipauth_ip_allow, tx_feipauth_ip_deny;;;;1-1-1');

$tempColumns = array(
	'tx_feipauth_ip_allow' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_feipauth_ip_allow',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_feipauth_ip_deny' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_feipauth_ip_deny',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups',$tempColumns,1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups','tx_feipauth_ip_allow, tx_feipauth_ip_deny;;;;1-1-1');

?>
