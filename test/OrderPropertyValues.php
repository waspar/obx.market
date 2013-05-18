<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

require_once dirname(__FILE__).'/_OrderProperty.php';

class OBX_Test_OrderPropertyValues extends OBX_Test_Lib_OrderProperty
{
	public function testAddOrder() {
		$this->_addOrder();
	}
	public function testGetNullValues() {

	}

	public function testAddValues() {

	}

	public function testGetValues() {

	}

	public function testUpdateValues() {

	}

	public function testRemoveValues() {

	}

	public function testDeleteOrder() {
		$this->_deleteOrder();
	}
}