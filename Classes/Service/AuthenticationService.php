<?php
namespace Alto\FeIpauth\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2015 Matthias Secker <secker@alto.de>
*
*  (c) 2010 Bernhard Kraft (kraftb@think-open.at)
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
 * Service 'IP Authentication' for the 'fe_ipauth' extension.
 *
 * @author	Matthias Secker <secker@alto.de>
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */



class AuthenticationService extends \TYPO3\CMS\Sv\AbstractAuthenticationService  {
	protected $doDebugIP = '';
//	protected $doDebugIP = '::1';				// For testing IPv6
//	protected $doDebugIP = '192.168.8.101';		// For testing IPv4
	protected $cacheTable = 'tx_feipauth_ipcache';

	/**
	 * @var \Alto\FeIpauth\Utility\Network
	 */
	protected $networkUtility = NULL;


	/**
	 * @var array
	 */
	protected $rules = array(
		'allow' => 1,
		'deny' => 2,
	);


	/**
	 * Initialize authentication service
	 *
	 * @param string Subtype of the service which is used to call the service.
	 * @param array Submitted login form data
	 * @param array Information array. Holds submitted form data etc.
	 * @param object Pointer to parent object instance
	 * @return void
	 */
	function initAuth($mode, $loginData, $authInfo, &$pObj) {
		parent::initAuth($mode, $loginData, $authInfo, $pObj);
		$this->pObj = &$pObj;
		// For debugging purposes
		if ($this->doDebugIP) {
			$this->authInfo['REMOTE_ADDR'] = $this->doDebugIP;
		}
	}

	/**
	 * Constructor: Initialize an networkUtility object instance
	 *
	 * @return void
	 */	
	public function __construct() {
		$this->networkUtility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('\\Alto\\FeIpauth\\Utility\\Network');

		// Settings for FE users
		$this->checkRuleFirst['FE']['user'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['checkRuleFirst_FE_user'];
		$this->allowOverride['FE']['user'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['allowOverride_FE_user'];
		$this->defaultRule['FE']['user'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['defaultRule_FE_user'];

		// Settings for FE groups
		$this->checkRuleFirst['FE']['group'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['checkRuleFirst_FE_group'];
		$this->allowOverride['FE']['group'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['allowOverride_FE_group'];
		$this->defaultRule['FE']['group'] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['defaultRule_FE_group'];
	}


	/**
	 * Checks if the current $OK value should be overriden
	 *
	 * @param integer The value determining wheter login is ok or not or if the authentication process should continue
	 * @param array IP cache record for the current user/ip
	 * @param integer The rule type for which to look in the cache records
	 * @param integer The value which should be returned instead of the value passed in the $OK variable if login is ok.
	 * @return integer The passed $OK variable probably overriden if appropriate cache entries have been found
	 */	
	protected function checkOverride($OK, $cacheRecords, $ruleType, $returnIfFound) {
		$matchFound = $this->entryFoundForRule($cacheRecords, $ruleType);
		if ($matchFound) {
			$OK = $returnIfFound;
		}
		return $OK;
	}
	
	/**
	 * This is a service class method which whill get called for authenticateing FE users
	 * If it returns "false" the user will not be authenticated
	 * If it returns "100" the login isn't denied, but neither is it graced. Other authentication methods will have to grant access
	 * If it returns "200" the login is granted and no other authentication methods are consolidated
	 *
	 * @param array Data of user
	 * @return integer If user could get authenticated or not and wheter to continue authentication process (0-200 ... see servic class API or description above
	 */	
	public function authUser($user)	{
		$myIP = $this->networkUtility->validateIP($this->authInfo['REMOTE_ADDR']);
		$myIP = $this->networkUtility->toCacheFormat($myIP);

		if ($myIP && ($cacheRecords = $this->networkUtility->getIPcache($user['uid'], 0, $myIP))) {
			$OK = $this->authenticationProcess('user', 100, false, $cacheRecords);
		} else {
				// When no cache records have been found for this user/IP combination set result to default
			$OK = $this->defaultRule('user', true, false);
		}

		if (!$OK) {
				// Failed login attempt (wrong IP) - write that to the log!
			$this->writelog(255,3,3,1, "FE-Login-attempt from %s (%s), username '%s', remote address does not match IP access controll entries!", Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']]));
		}
		return $OK;
	}
	
	

	/**
	 * Authenticate a FE-group by IP. When there are appropriate IP access control entries for this group allow login.
	 *
	 * @param array  The data of the FE-User (fe_users record)
	 * @param array  The fe_groups record for the group being authenticated
	 * @return boolean Returns "true" if a user of the checked group is allowed to login
	 */
	public function authGroup($user, $group) {
		$myIP = $this->networkUtility->validateIP($this->authInfo['REMOTE_ADDR']);
		$myIP = $this->networkUtility->toCacheFormat($myIP);
		$cacheRecords = $this->networkUtility->getIPcache(0, $group['uid'], $myIP);


		if ($myIP && ($cacheRecords = $this->networkUtility->getIPcache(0, $group['uid'], $myIP))) {
			$valid = $this->authenticationProcess('group', true, false, $cacheRecords);
		} else {
			// When no cache records have been found for this group/IP combination set result to default
			$valid = $this->defaultRule('group', true, false);
		}

		if (!$valid) {
			// Failed login attempt (wrong IP) - write that to the log!
			$this->writelog(255,3,3,1, "FE-Usergroup '%s' (%s) is not allowed to login from address %s (%s). Remote address does not match IP access controll entries!", array($group['title'], $group['uid'], $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']));
		}

		return $valid;
	}

	/**
	 * This method performs the authentication process/steps. It checks the rules in defined order and if no matching
	 * IP entries are found it returns the default rule
	 *
	 * @param string The type for which to perform the authentication process (either "group" or "user")
	 * @param mixed The value to return in the case of a sucessfull authentication
	 * @param mixed The value to return for a failed authentication
	 * @param array All cache records for the current user/group and IP
	 * @return mixed Either the value stored in $allowSetting or $denySetting depending on wheter the authentication succeded
	 */	
	private function authenticationProcess($type, $allowSetting, $denySetting, $cacheRecords) {
		$error = false;

			// Variable check
		switch ($type) {
			case 'user':
			case 'group':
			break;
			default:
				return $denySetting;
			break;
		}
			// Determine rules to check first and what are the other rules and return values for each
		switch ($this->checkRuleFirst['FE'][$type]) {
			case 'deny':
				$firstRule = 'deny';
				$firstRuleSetting = $denySetting;
				$otherRule = 'allow';
				$otherRuleSetting = $allowSetting;
			break;
			case 'allow':
				$firstRule = 'allow';
				$firstRuleSetting = $allowSetting;
				$otherRule = 'deny';
				$otherRuleSetting = $denySetting;
			break;
			default:
				$error = true;
				$current = $denySetting;
			break;
		}

		if (!$error) {
				// Check if entries are found for rule which to check first
			$matchFound = $this->entryFoundForRule($cacheRecords, $this->rules[$firstRule]);
			if ($matchFound) {
				$current = $firstRuleSetting;
					// When override is allowed check if there are overriding IPs set
				if ($this->allowOverride['FE'][$type] === 'yes') {
					$current = $this->checkOverride($current, $cacheRecords, $this->rules[$otherRule], $otherRuleSetting);
				}
			} else {
					// If no entries are found for first rule, check other rule
				$matchFound = $this->entryFoundForRule($cacheRecords, $this->rules[$otherRule]);
				if ($matchFound) {
					$current = $otherRuleSetting;
				} else {
						// When no entries are found for first or other rule, set default
					$current = $this->defaultRule($type, $allowSetting, $denySetting);
				}
			}
		}

		return $current;
	}

	/**
	 * This method returns the appropriate default value/rule for the passed type ("group" or "user")
	 *
	 * @param string The type for which to perform the authentication process (either "group" or "user")
	 * @param mixed The value to return for the "allow" default rule
	 * @param mixed The value to return for the "deny" default rule
	 * @return mixed The default value set for the passed type
	 */	
	private function defaultRule($type, $allowSetting, $denySetting) {
		// Variable check
		switch ($type) {
			case 'user':
			case 'group':
			break;
			default:
				return $denySetting;
			break;
		}
		switch ($this->defaultRule['FE'][$type]) {
			case 'allow':
				return $allowSetting;
			break;
			case 'deny':
				return $denySetting;
			break;
			default:
				return $denySetting;
			break;
		}
	}


	/**
	 * Traverses over all passed cacheRecords looking if there is one having the requested rule-type (allow or deny)
	 * TODO: Make the rule-type an enum in DB (easier to read than 1/2 for allow/deny)
	 *
	 * @param array All cache records which should get checked
	 * @param integer The rule-type to look for
	 * @return boolean wheter a matching cache records was found (true) or not (false)
	 */	
	protected function entryFoundForRule($cacheRecords, $ruleType) {
		foreach ($cacheRecords as $cacheEntry) {
			if (intval($cacheEntry['rule_type']) === $ruleType) {
				return true;
			}
		}
		return false;
	}



}


?>
