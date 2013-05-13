<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

OBX_Market_TestCase::includeLang(__FILE__);

class OBX_Test_Lib_Order extends OBX_Market_TestCase
{


	static protected $_arOrderList = array();
	static public function setUpBeforeClass() {
		self::$_arOrderList = array(
			0 => array(),
			1 => array(),
			2 => array(),
			3 => array()
		);
	}

	public function _addOrder() {
		$orderID = OBX_OrderList::add(array('USER_ID' => 1));
		if($orderID<1) {
			$arError = OBX_OrderList::popLastError('ARRAY');
			$this->assertGreaterThan(0, $orderID, 'Error: code: "'.$arError['CODE'].'"; test: "'.$arError['TEXT'].'"');
		}
		self::$_arOrderList[] = array(
			'ID' => $orderID
		);
	}

	public function _deleteOrder() {
		foreach(self::$_arOrderList as &$arOrderDesc) {
			OBX_OrderList::delete($arOrderDesc['ID']);
		} unset($arOrderDesc);
	}
}