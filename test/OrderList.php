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

require_once dirname(__FILE__).'/_Order.php';

OBX_Market_TestCase::includeLang(__FILE__);

class OBX_Test_OrderList extends OBX_Test_Lib_Order
{


	public function testCreateOrder() {
		$this->_addOrder();
	}

	public function testGetOrderList() {
		$arFilter = array('ID' => array());
		//print_r(self::$_arOrderList);
		foreach(self::$_arOrderList as &$arOrderDesc) {
			$arFilter['ID'][] = $arOrderDesc['ID'];
		} unset($arOrderDesc);
		//print_r($arFilter);
		$arOrderList = OrderList::getListArray(null, $arFilter);
		//print_r($arOrderList);
	}

	public function testUpdateOrder() {

	}

	public function testDeleteOrder() {
		$this->_deleteOrder();
	}
}
