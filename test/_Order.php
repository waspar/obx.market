<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\OrderList;
use OBX\Market\OrderListDBS;

OBX_Market_TestCase::includeLang(__FILE__);

class OBX_Test_Lib_Order extends OBX_Market_TestCase
{
	static protected $_arOrderList = array();
	static public function setUpBeforeClass() {
		self::$_arOrderList = array();
	}

	/**
	 * @return array
	 */
	static public function & getTestOrderList() {
		return self::$_arOrderList;
	}

	public function _addOrder() {
		$this->_getTestUser();
		$orderID = OrderList::add(array('USER_ID' => self::$_arTestUser['ID']));
		if($orderID<1) {
			$arError = OrderList::popLastError('ARRAY');
		}
		$this->assertGreaterThan(0, $orderID, 'Error: code: "'.$arError['CODE'].'"; test: "'.$arError['TEXT'].'"');
		self::$_arOrderList[] = array(
			'ID' => $orderID
		);
	}

	public function _deleteOrder() {
		foreach(self::$_arOrderList as &$arOrderDesc) {
			$bSuccess = OrderList::delete($arOrderDesc['ID']);
			if(!$bSuccess) {
				$arError = OrderList::popLastError('ARRAY');
				$this->fail('Error: can\'t delete order: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
		} unset($arOrderDesc);
	}
}