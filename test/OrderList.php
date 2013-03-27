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

class OBX_Test_OrderList extends OBX_Market_TestCase
{
	static protected $_arOrdersList = array();
	public function testPrepareTests() {
		self::$_arOrdersList = array(
			0 => array(),
			1 => array(),
			2 => array(),
			3 => array()
		);
	}

	public function testCreateOrder() {
		$orderID = OBX_OrdersList::add();
		if($orderID<1) {
			$arError = OBX_OrdersList::popLastError('ARRAY');
			$this->assertGreaterThan(0, $orderID, 'Error: code: "'.$arError['CODE'].'"; test: "'.$arError['TEXT'].'"');
		}
		self::$_arOrdersList[] = array(
			'ID' => $orderID
		);
	}

	public function testUpdateOrder() {

	}

	public function testDeleteOrder() {

	}
}
