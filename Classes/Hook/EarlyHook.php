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
 * This is the earliest hook for TYPO3 FE rendering
 * It is used for setting a simulated IP
 *
 * @author  Matthias Secker <secker@alto.de>
 * @author	Bernhard Kraft <kraftb@think-open.at>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */


class EarlyHook {

	/*
	 * This hook method sets a simluated IP for debugging purposes
	 *
	 * @param array Passed parameters (shoudl be empty)
	 * @param object Usually a pointer to the parent object instance but in this case just the same variable as for the first parameter is passed
	 * @return void
	 */
	public function simulateIP($params, &$parentObject) {
		if ($simIP = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fe_ipauth']['simulateIP']) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
				$GLOBALS['ORIG_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
				$_SERVER['REMOTE_ADDR'] = $simIP;
			}
		}
	}



}




?>
