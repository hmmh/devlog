<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Fabien Udriot <fabien.udriot@ecodev.ch>
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
*
* $Id$
***************************************************************/

/**
 * Classes used as ExtDirect's router
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package	TYPO3
 * @subpackage	tx_devlog
 */
class tx_devlog_remote {

	/**
	 * This method returns the message's content
	 *
	 * @param	array			$PA: information related to the field
	 * @param	t3lib_tceform	$fobj: reference to calling TCEforms object
	 * @return	string	The HTML for the form field
	 */
	public function concatenateStrings($string1, $string2) {
		return $string1 . ' ' . $string2;
	}

	public function testMe($string1, $string2) {
		return $string1 . ' ' . $string2;
	}

	public function myMethod($string1, $string2) {
		return $string1 . ' ' . $string2;
	}

	/**
	 * Fetches log depending on parameters
	 * 
	 * @global t3lib_DB $TYPO3_DB
	 * @return array
	 */
	public function indexAction() {
		global $TYPO3_DB;

		// ExtJS api: http://www.extjs.com/deploy/dev/docs/?class=Ext.data.JsonReader
//		metaData: {
//        // used by store to set its sortInfo
//        "sortInfo":{
//           "field": "name",
//           "direction": "ASC"
//        },
//        // paging data (if applicable)
//        "start": 0,
//        "limit": 2,
//        // custom property
//        "foo": "bar"
//    },
		$metaData['idProperty'] = 'uid';
		$metaData['root'] = 'records';
		$metaData['totalProperty'] = 'total';
		$metaData['successProperty'] = 'success';
		$metaData['fields'] = array(
			array('name' => 'uid', 'type' => 'int'),
			array('name' => 'pid', 'type' => 'int'),
			array('name' => 'crdate', 'type' => 'date', 'dateFormat' => 'timestamp'),
			array('name' => 'crmsec', 'type' => 'date', 'dateFormat' => 'timestamp'),
			array('name' => 'cruser_id', 'type' => 'int'),
			array('name' => 'severity', 'type' => 'int'),
			array('name' => 'extkey', 'type' => 'string'),
			array('name' => 'msg', 'type' => 'string'),
			array('name' => 'location', 'type' => 'string'),
			array('name' => 'line', 'type' => 'string'),
			array('name' => 'data_var', 'type' => 'string'),
			array('name' => 'cruser_formated', 'type' => 'string'),

		);

		#$TYPO3_DB->SELECTquery('*', 'tx_devlog', '', $groupBy = '', $orderBy = 'uid DESC', $limit = 25);

		$records = $TYPO3_DB->exec_SELECTgetRows('*', 'tx_devlog', '', $groupBy = '', $orderBy = 'uid DESC', $limit = 25);
		foreach ($records as &$record) {
			$record['cruser_formated'] = $this->getRecordDetails('be_users', $record['cruser_id']);
		}

		$datasource['metaData'] = $metaData;
		$datasource['total'] = count($records);
		$datasource['records'] = $records;
		$datasource['success'] = TRUE;
		// For ExtDirect
		//return $datasource;

		// For JsonReader
		echo json_encode($datasource);
	}


	/**
	 * This method gets the title and the icon for a given record of a given table
	 * It returns these as a HTML string
	 *
	 * @param	string		$table: name of the table
	 * @param	integer		$uid: primary key of the record
	 * @return	string		HTML to display
	 */
	protected function getRecordDetails($table = 'be_users', $uid) {
		global $TCA;
		$row = t3lib_BEfunc::getRecord($table, $uid);
		$elementTitle = t3lib_BEfunc::getRecordTitle($table, $row, 1);
		$spriteName = $TCA['be_users']['ctrl']['typeicon_classes'][$row['admin']];
		$elementIcon = t3lib_iconWorks::getSpriteIcon($spriteName);
		return $elementIcon . $elementTitle;
	}
}

?>