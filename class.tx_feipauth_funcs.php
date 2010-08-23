<?php
/***************************************************************
*  Copyright notice
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
 * Class for handling and working with IP addresses (IPv4 and IPv6)
 *
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


class tx_feipauth_funcs {
	var $cacheTable = 'tx_feipauth_ipcache';


	/*
	 * This methods converts the binary represenations of the passed IP addresses to a string representation
	 *
	 * @param array An array containing either an IPv4 or an IPv6 address in binary form
	 * @return string The textual string representation of the passed address
	 */
	public function IP_to_string($ipAddress) {
		$ipString = '';
		$address = $ipAddress[0];
		$netmask = $ipAddress[1];
		if (is_array($address) && is_array($netmask)) {
			$ipString = $this->IPv6_to_string($address, $netmask);
		} elseif (!(is_array($address) || is_array($netmask))) {
			$ipString = $this->IPv4_to_string($address, $netmask);
		}
		return $ipString;
	}

	/*
	 * This methods converts a binary represenations of an IPv6 address to its string representation
	 *
	 * @param array An array of 4 integers representing an IPv6 address in binary form
	 * @param array An array of 4 integers representing an IPv6 netmask in binary form
	 * @return string The textual string representation of the passed address
	 */
	public function IPv6_to_string($address, $netmask) {
			// First determine if there are any zero-areas which can get removed
		$zero_map = array();
		$zero_start = false;
		$zero_length = 0;
		$cnt = 0;

			// Split 32-bit int values in two 16-bit values
		foreach ($address as $address_part) {
			$splitted_address[] = intval($address_part/0x10000);
			$splitted_address[] = intval($address_part%0x10000);
		}
		$address = $splitted_address;

		foreach ($address as $address_part) {
			if (!$address_part) {
				if ($zero_start !== false) {
					$zero_length++;
				} else {
					$zero_start = $cnt;
					$zero_length = 1;
				}
			} else {	
				if ($zero_start !== false) {
					$zero_map[$zero_start] = $zero_length;
					$zero_start = false;
					$zero_length = 0;
				}
			}
			$cnt++;
		}
		if ($zero_start !== false) {
			$zero_map[$zero_start] = $zero_length;
			$zero_start = false;
			$zero_length = 0;
		}
		asort($zero_map, SORT_NUMERIC);
		$zero_pos = false;
		if (count($zero_map)) {
			$zero_pos = array_pop(array_keys($zero_map));
		}

		$cnt = 0;
		$result = array();
		$zero_remove = false;
		foreach ($address as $address_part) {
			if ($zero_remove && !$address_part) {
				continue;
			} else {
				if ($zero_remove) {
					$zero_remove = false;
				}
				if ($cnt === $zero_pos) {
					$result[] = '';
					$zero_remove = true;
				} else {
					$result[] = sprintf('%x', $address_part);
				}
			}
			$cnt++;
		}
		$resultString = implode(':', $result);
		if (substr($resultString, 0, 1) == ':') {
			$resultString = ':'.$resultString;
		} elseif (substr($resultString, -1, 1) == ':') {
			$resultString .= ':';
		}
		$maskBits = $this->countOnes_v6($netmask);
		return $resultString.'/'.$maskBits;
	}

	/*
	 * This methods converts a binary represenations of an IPv4 address to its string representation
	 *
	 * @param array A integer representing an IPv4 address in binary form
	 * @param array A integer representing an IPv4 netmask in binary form
	 * @return string The textual string representation of the passed address
	 */
	public function IPv4_to_string($address, $netmask) {
		$ipString = long2ip($address);
		$maskBits = $this->countOnes($netmask);
		return $ipString.'/'.$maskBits;
	}

	/*
	 * This methods parses and validates the given IP address and returns it in interal (binary) array form or false on error
	 *
	 * @param string An IP address as supplied by the user
	 * @return mixed Either the parsed and validated IP address or false on error
	 */
	public function validateIP($ipAddress) {
		if (strpos($ipAddress, ':') !== false) {
			return $this->validateIP_IPv6($ipAddress);
		} else {
			return $this->validateIP_IPv4($ipAddress);
		}
	}

	/**
	 * Returns all IP cache entries found for the user/group passed where the Network-IP matches the IP (passed as $searchIP argument) combined with the entries netmask
	 *
	 * @param integer Return cache entries for this user
	 * @param integer Return cache entries for this group
	 * @param array An IP in internal representation (binary format) for which to search in the IP cache
	 * @return array All cache records found for user/group and IP searched for
	 */	
	public function getIPcache($user, $group, $searchIP, $types = array()) {
		$fields = array('user_id', 'group_id', 'rule_type', 'is_v6', 'address_0', 'address_1', 'address_2', 'address_3', 'netmask_0', 'netmask_1', 'netmask_2', 'netmask_3', 'network_0', 'network_1', 'network_2', 'network_3', 'host_0', 'host_1', 'host_2', 'host_3');
		$searchParts = array();
		if ($searchIP) {
			for ($x = 0; $x < 4; $x++) {
				$searchParts[] = 'network_'.$x.' = (netmask_'.$x.' & '.$searchIP[0][$x].')';
			}
			$searchParts[] = 'is_v6 = '.($searchIP[2] ? 1 : 0);
		}
		$groupBy = '';
		$orderBy = 'user_id, group_id';
		if ($user) {
			if ($user < 0) {
				$fields = array('user_id', 'rule_type');
				$groupBy = 'user_id, rule_type';
				$orderBy = 'user_id';
				$searchParts[] = 'user_id>0';
			} else {
				$searchParts[] = 'user_id='.$user;
			}
		}
		if ($group) {
			if ($group < 0) {
				$fields = array('group_id', 'rule_type');
				$groupBy = 'group_id, rule_type';
				$orderBy = 'group_id';
				$searchParts[] = 'group_id>0';
			} else {
				$searchParts[] = 'group_id='.$group;
			}
		}
		if ($types) {
			$searchParts[] = 'rule_type IN ('.implode(',', $types).')';
		}
		$searchString = implode(' AND ', $searchParts);
		$cacheRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(implode(',', $fields), $this->cacheTable, $searchString, $groupBy, $orderBy);
		
		return $cacheRecords;
	}

	/**
	 * Converts an IPv4 Address to look like an IPv6 address (in the cache the difference between both is only a flag).
	 * Sets the "is_v6" flag for real IPv6 addresses.
	 *
	 * @param array The IP address as returned from the "->validateIP" method. (Can be v4 or v6 format)
	 * @return array The IP in internal cache format (v6) or false on error
	 */	
	public function toCacheFormat($ip) {
		if (is_array($ip[0]) && is_array($ip[1])) {
			$ip[2] = true;
			return $ip;
		} elseif (!(is_array($ip[0])) && !is_array($ip[1])) {
			return array(
				array(
					$ip[0],
					0,
					0,
					0,
				),
				array(
					$ip[1],
					0,
					0,
					0,
				),
				false
			);
		}
		return false;
	}


	/************************************************************************************
	 *
	 * METHODS FOR INTERNAL USAGE
	 *
	 * This methods are only used internally in the class and must not be called from outside
	 *
	 ************************************************************************************/

	/*
	 * This methods counts the number of leading 1's in the passed binary IPv4 netmask
	 *
	 * @param integer A integer representing an IPv4 netmask in binary form
	 * @return string The number of leading zeros representing the netmask in /xx form
	 */
	protected function countOnes($netmask) {
		$ones = 0;
		for ($x = 31; $x >= 0; $x--) {
			if ($netmask & (1 << $x)) {
				$ones++;
			} else {
				break;
			}
		}
		return $ones;
	}

	/*
	 * This methods counts the number of leading 1's in the passed binary IPv6 netmask
	 *
	 * @param array An array of 4 integers representing an IPv6 netmask in binary form
	 * @return string The number of leading zeros representing the netmask in /xx form
	 */
	protected function countOnes_v6($netmask) {
		$ones = 0;
		for ($x = 0; $x < 8; $x++) {
			if ($netmask[$x] == 0xffffffff) {
				$ones += 32;
			} elseif ($netmask[$x] == 0) {
				break;
			} else {
				$ones += $this->countOnes($netmask[$x]);
			}
		}
		return $ones;
	}


	/*
	 * This methods parses and validates the given IPv6 address and returns it in interal (binary) array form or false on error
	 * @see function validateIP()
	 *
	 * @param string An IPv6 address as supplied by the user
	 * @return mixed Either the parsed and validated IP address or false on error
	 */
	protected function validateIP_IPv6($ipAddress) {
		$parts = t3lib_div::trimExplode('/', $ipAddress);
		if ((count($parts) == 1) || (count($parts) == 2)) {
			$address = $parts[0];
			$netmask = (count($parts) > 1) ? $parts[1] : '';
			$address_int = array();
			$netmask_int = array();
			if (strlen($netmask)) {
				if (t3lib_div::testInt($netmask)) {
					$netmask_int = $this->netmask_digits2int_v6($netmask);
					if (!is_array($netmask_int)) {
						return false;
					}
				} else {
					return false;
				}
			} else {
				$netmask_int[0] = 0xffffffff;
				$netmask_int[1] = 0xffffffff;
				$netmask_int[2] = 0xffffffff;
				$netmask_int[3] = 0xffffffff;
			}
			$address_parts = $this->sanitizeAddressIPv6($address);
			if ($address_parts) {
				$address_int = $this->IPv6_to_int($address_parts);
				return array($address_int, $netmask_int);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/*
	 * This methods parses and validates the given IPv4 address and returns it in interal (binary) array form or false on error
	 * @see function validateIP()
	 *
	 * @param string An IPv4 address as supplied by the user
	 * @return mixed Either the parsed and validated IP address or false on error
	 */
	protected function validateIP_IPv4($ipAddress) {
		$parts = t3lib_div::trimExplode('/', $ipAddress);
		if ((count($parts) == 1) || (count($parts) == 2)) {
			$address = $parts[0];
			$netmask = $parts[1];
			$ip_int = ip2long($address);

			if (strlen($netmask)) {
				if (t3lib_div::testInt($netmask)) {
					$netmask_int = $this->netmask_digits2int($netmask);
				} else {
					$netmask_int = ip2long($netmask);
				}
			} elseif (strpos($address, '*') !== false) {
				$addressAndNetmask = $this->wildcardedIP_to_addressAndNetmask($address);
				if (!$addressAndNetmask) {
					return false;
				}
				$ip_int = $addressAndNetmask[0];
				$netmask_int = $addressAndNetmask[1];
			} else {
					// When no netmask is given the passed IP specifies a host address (255.255.255.255)
				$netmask_int = 0xffffffff;
			}
			if (!$this->netmask_ok($netmask_int)) {
				return false;
			}

			return array($ip_int, $netmask_int);
		} else {
			return false;
		}
	}

	/*
	 * This methods converts 8 four-letter hexadecimal strings containing an IPv6 address to 4 32-bit unsigned integers
	 *
	 * @param array An IPv6 address made up of 8 array values each containing a 0-4 letter hexadecimal string
	 * @return array An array containing the IPv6 address in 4 32-bit unsigned integers (internal binary form)
	 */
	protected function IPv6_to_int($address_parts) {
		$cnt = 0;
		$result = array();
		$tmpVal = 0;
		foreach ($address_parts as $address_part) {
			$tmpVal = $tmpVal << 16;
			$tmpVal += hexdec($address_part);
			$cnt++;
			if (!($cnt % 2)) {
				$result[intval($cnt/2)-1] = $tmpVal;
				$tmpVal = 0;
			}
		}
		return $result;
	}

	/*
	 * This methods sanitizes the passed IPv6 address string to an array of 8 values each containing an 0-4 letter hexadecimal string
	 * This method performs the necessary expansion of the IPv6 "::" syntax of left away repeating 0's in the address.
	 * i.e: "::1" passed to the method will return an array (0, 0, 0, 0, 0, 0, 0, 1)
	 * Expansion is performed only once.
	 *
	 * @param string An IPv6 address in the common string notation
	 * @return array An array containing the IPv6 address expanded to 8 hexadecimal values
	 */
	protected function sanitizeAddressIPv6($address) {
		if (substr($address, 0, 1) === ':') {
			if (substr($address, 0, 2) === '::') {
				$address = substr($address, 1);
			} else {
				return false;
			}
		}
		if (substr($address, -1, 1) === ':') {
			if (substr($address, -2, 2) === '::') {
				$address = substr($address, 0, -1);
			} else {
				return false;
			}
		}
		$address_parts = t3lib_div::trimExplode(':', $address);
		$addressPartCount = count($address_parts);
		if ($addressPartCount > 8) {
			return false;
		}
		$saneAddress = array();
		$haveExpanded = false;
		foreach ($address_parts as $idx => $address_part) {
			$address_part = strtolower($address_part);
			if (!preg_match('/^[0-9a-f]{0,4}$/', $address_part)) {
				return false;
			}
			if (!$address_part) {
				if ($haveExpanded) {
					return false;
				}
				for ($x = 0; $x <= (8-$addressPartCount); $x++) {
					$saneAddress[] = 0;
				}
				$haveExpanded = true;
			} else {
				$saneAddress[] = $address_part;
			}
		}
		return $saneAddress;
	}


	/*
	 * This methods converts the passed IPv6 netmask (given as value in the range from 0 to 128) to a binary netmask suitable for boolean operations.
	 *
	 * @param integer The netmask as number between 0 and 128 representing the amount of leading 1's in the binary netmask
	 * @return array An array containing 4 unsigned integers representing an IPv6 netmask
	 */
	protected function netmask_digits2int_v6($netmask) {
		$netmask = intval($netmask);
		$netmask_int = array();
			// Must not be negative. Must not be larger than 16bytes * 8bits/byte = 128bits
		if (($netmask < 0) || ($netmask > 16*8)) {
			return false;
		}
			// 4 Steps, each time processing 32bits ==> 128bits
		for ($x = 0; $x < 4; $x++) {
			if ($netmask > 32) {
				$netmask_int[$x] = 0xffffffff;
			} elseif ($netmask > 0) {
				$netmask_int[$x] = $this->netmask_digits2int($netmask);
			} else {
				$netmask_int[$x] = 0;
			}
			$netmask -= 32;
			
		}
		return $netmask_int;
	}

	/*
	 * This methods converts the passed IPv4 netmask (given as value in the range from 0 to 32) to a binary netmask suitable for boolean operations.
	 *
	 * @param integer The netmask as number between 0 and 32 representing the amount of leading 1's in the binary netmask
	 * @return integer The IPv4 netmask as unsigned integer
	 */
	protected function netmask_digits2int($netmask) {
		$netmask = intval($netmask);
		$netmaskInt = false;
		if (($netmask >= 0) && ($netmask <= 32)) {
			$netmaskInt = 0;
			for ($bit = 0; $bit < 32; $bit++) {
				$netmaskInt = $netmaskInt << 1;
				if ($netmask>0) {
					$netmaskInt |= 1;
				}
				$netmask--;
			}
		}
		return $netmaskInt;
	}

	/*
	 * This methods checks wheter a netmask has leading 1's and trailing 0's and no 1's after the first 0
	 *
	 * @param integer The netmask as number unsigned integer
	 * @return boolean Wheter the passed netmask is ok or not
	 */
	protected function netmask_ok($netmaskInt) {
			// Check if the netmask begins with 1's and ends with 0's. There must not
			// be any 1's after the first 0
		$hostPart = false;
		for ($x = 31; $x >= 0; $x--) {
			$testBit = $netmaskInt & (1 << $x);
			if ($hostPart && $testBit) {
					// When we are in the host-part and encounter a "1" the netmask is invalid
				return false;
			} elseif (!$hostPart) {
					// When the testBit is set we are still in the net-part
				$hostPart = $testBit ? false : true;
			}
		}
		return true;
	}

	/*
	 * When a wildcarded IP-Address is supplied like 192.168.*.* this method converts such a representation to an form suitable for further processing
	 *
	 * @param string An IPv4 address which contains the wildcard "*"
	 * @return array The first entry in the array will contain the address/network as unsigned integer while the second value in the array will contain the netmask as unsigned integer
	 */
	protected function wildcardedIP_to_addressAndNetmask($address) {
		$ip_parts = t3lib_div::trimExplode('.', $address);
		if (count($ip_parts) != 4) {
			return false;
		}
		$netmaskInt = 0;
		$ipInt = 0;
		$hostPart = false;
		foreach ($ip_parts as $idx => $ip_part) {
			$netmaskInt = $netmaskInt << 8;
			$ipInt = $ipInt << 8;
			if (!strcmp($ip_part, '*')) {
				$hostPart = true;
			} else {
				if ($hostPart) {
					return false;
				}
				$ip_part = intval($ip_part);
				if (($ip_part < 0) || ($ip_part > 255)) {
					return false;
				}
				$ipInt += $ip_part;
				$netmaskInt += 255;
			}
		}
		return array($ipInt, $netmaskInt);
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fe_ipauth/class.tx_feipauth_funcs.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fe_ipauth/class.tx_feipauth_funcs.php']);
}

?>
