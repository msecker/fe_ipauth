<?php
namespace Alto\FeIpauth\Hook;
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Matthias Secker <secker@alto.de>
*
*  (c) 2010 Bernhard Kraft <kraftb@think-open.at>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/** 
 * Hooks for TCEmain
 *
 * @author	Matthias Secker <secker@alto.de>
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */



class TceMain {
	protected $cacheTable = 'tx_feipauth_ipcache';

	/**
	 * @var \Alto\FeIpauth\Utility\Network
	 */
	protected $networkUtility = NULL;

	protected $datamap;
	protected $ipListFields = array(
		'tx_feipauth_ip_allow' => 1,
		'tx_feipauth_ip_deny' => 2,
	);


	/*
	 * The constructor for this class
	 *
	 * @return void
	 */
	public function __construct() {
		$this->networkUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\Alto\\FeIpauth\\Utility\\Network');
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['extraIpListFields'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['extraIpListFields'] as $field => $rule_type) {
				$this->ipListFields[$field] = $rule_type;
			}
		}
	}

	/*
	 * This is the hook method being called from t3lib_TCEmain
	 *
	 * @param string The operation which was currently being performed (update, insert, etc.)
	 * @param string The table for which this operation was performed
	 * @param mixed The UID for which this operation was performed (can be a NEW0123... string when a new records was inserted)
	 * @param array The fields and the values they have been set to
	 * @param t3lib_TCEmain A pointer to the object instance which called this hook.
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, &$parentObject) {
		if ((($table == 'fe_users') || ($table == 'fe_groups')) && (($status == 'update') || ($status == 'insert'))) {
			if (!$GLOBALS['T3_VARS']['fe_ipauth']['inHook']) {
					// This variable helps avoiding infinite recursions of call to this hook
				$GLOBALS['T3_VARS']['fe_ipauth']['inHook'] = true;

				if (substr($id, 0, 3) === 'NEW') {
					$id = $parentObject->substNEWwithIDs[$id];
				}
				$user = ($table === 'fe_users') ? $id : 0;
				$group = ($table === 'fe_groups') ? $id : 0;

				$this->datamap = array();
				$this->processFields($fieldArray, $table, $id, $user, $group);

				$TCE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$TCE->stripslashes_values = false;
				$TCE->start($this->datamap, array());
				$TCE->process_datamap();

				$GLOBALS['T3_VARS']['fe_ipauth']['inHook'] = false;
			}
		}
	}

	/*
	 * Processes the fields containing an IP-List
	 *
	 * @param array The fields and the values they have been set to
	 * @param string The table for which this operation was performed
	 * @param integer The UID for which this operation was performed
	 * @param integer The user id which is currently being processed
	 * @param integer The group id which is currently being processed
	 * @return void
	 */
	public function processFields($fieldArray, $table, $id, $user, $group) {
		foreach ($this->ipListFields as $ipField => $rule) {
			if (isset($fieldArray[$ipField])) {
				$ipList = $fieldArray[$ipField];
				$ipArray = $this->validateIPs($ipList);

				$this->setIPcache($user, $group, $rule, $ipArray);
				$validList = $this->IPs_to_list($ipArray);
				$this->datamap[$table][$id][$ipField] = $validList;
			}
		}
	}

	/*
	 * This methods sets the IP addreses passed in the array in the SQL cache tables
	 *
	 * @param integer The uid of the user for which this cache entries get set (if a fe_users record got updated)
	 * @param integer The uid of the group for which this cache entries get set (if a fe_groups record got updated)
	 * @param integer The rule type which the entries in the $ipArray are for
	 * @param array An array of IP addresses to set in the cache table
	 * @return void
	 */
	protected function setIPcache($user, $group, $rule, $ipArray) {

		$rule = intval($rule);
		if ($user) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->cacheTable, 'user_id='.$user.' AND (rule_type='.$rule.' OR rule_type=0)');
		} elseif ($group) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery($this->cacheTable, 'group_id='.$group.' AND (rule_type='.$rule.' OR rule_type=0)');
		}

		foreach ($ipArray as $ipSet)  {
			$address = $ipSet[0];
			$netmask = $ipSet[1];

			if (is_array($address)) {
				$insertData = $this->getCacheArray_v6($address, $netmask);
			} else {
				$insertData = $this->getCacheArray_v4($address, $netmask);
			}
			$insertData['rule_type'] = $rule;
			$insertData['user_id'] = $user;
			$insertData['group_id'] = $group;

			$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->cacheTable, $insertData);
		}
	}

	/*
	 * This methods returns an array appropriate for INSERT queries for setting IPv4 addresses in the cache table
	 *
	 * @param integer The IPv4 address as unsigned integer
	 * @param integer The IPv4 netmask as unsigned integer
	 * @return void
	 */
	protected function getCacheArray_v4($address, $netmask) {
		$records =  array(
			'address_0' => sprintf("%u", $address),
			'netmask_0' => sprintf("%u", $netmask),
			'network_0' => sprintf("%u", $address & $netmask),
			'host_0' => sprintf("%u", $address & (~$netmask & 0xffffffff)),
		);

		return $records;
	}

	/*
	 * This methods returns an array appropriate for INSERT queries for setting IPv6 addresses in the cache table
	 *
	 * @param array An array of 4 unsigned integers representing the IPv6 address
	 * @param array An array of 4 unsigned integers representing the IPv6 netmask
	 * @return void
	 */
	protected function getCacheArray_v6($address, $netmask) {
		$insertData = array();
		for ($x = 0; $x < 4; $x++) {
			$addressItem = $address[$x];
			$netmaskItem = $netmask[$x];
			$insertData['address_'.$x] = $addressItem;
			$insertData['netmask_'.$x] = $netmaskItem;
			$insertData['network_'.$x] = $addressItem & $netmaskItem;
			$insertData['host_'.$x] = $addressItem & (~$netmaskItem & 0xffffffff);
		}
		$insertData['is_v6'] = 1;
		return $insertData;
	}

	/*
	 * This methods iterates over the list of IP addresses and parses and validates each of them
	 *
	 * @param string A list of IP addresses as supplied by the user
	 * @return array The passed string list cleand up and in array form for internal usage
	 */
	protected function validateIPs($ipList) {
		$ipAddresses = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $ipList, 1);
		$result = array();
		foreach ($ipAddresses as $ipAddress) {
			$validIP = $this->networkUtility->validateIP($ipAddress);
			if ($validIP) {
				$result[] = $validIP;
			}
		}
		return $result;
	}

	/*
	 * This methods traverses over the passed array and converts all binary represenations of IP addresses in the array to a string list
	 *
	 * @param array An mixed array of IPv4 and IPv6 addresses in binary form
	 * @return string The textual string representation of the passed addresses
	 */
	public function IPs_to_list($ipArray) {
		$result = array();
		foreach ($ipArray as $ipAddress) {
			if ($ipString = $this->networkUtility->IP_to_string($ipAddress)) {
				$result[] = $ipString;
			}
		}
		$result = array_unique($result);
		// It can be the case that an invalid value was found when the addresses got parsed
		// This will result in an 0.0.0.0/32 IPv4 address which gets removed here
		// TODO: Check that illegal values get not parsed to this IP address
		$result = array_diff($result, array('0.0.0.0/32'));
		return implode(', ', $result);
	}

}



?>
