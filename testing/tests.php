#!/usr/bin/php
<?php

require_once('../../../../t3lib/class.t3lib_div.php');
require_once('../class.tx_feipauth_tcemain.php');

$testObject = t3lib_div::makeInstance('tx_feipauth_tcemain');

$testObject->processDatamap_afterDatabaseOperations('update', 'fe_users', 4, array('tx_feipauth_ip_list' => '10.0.0.0/8,::1'), $testObject);



?>
