<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$tempColumns = array(
	'tx_feipauth_ip_allow' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/locallang_db.php:fe_users.tx_feipauth_ip_allow',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_feipauth_ip_deny' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/locallang_db.php:fe_users.tx_feipauth_ip_deny',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
);


t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_users','tx_feipauth_ip_allow, tx_feipauth_ip_deny;;;;1-1-1');

$tempColumns = array(
	'tx_feipauth_ip_allow' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/locallang_db.php:fe_groups.tx_feipauth_ip_allow',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
	'tx_feipauth_ip_deny' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:fe_ipauth/locallang_db.php:fe_groups.tx_feipauth_ip_deny',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
);

t3lib_div::loadTCA('fe_groups');
t3lib_extMgm::addTCAcolumns('fe_groups',$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('fe_groups','tx_feipauth_ip_allow, tx_feipauth_ip_deny;;;;1-1-1');

?>
