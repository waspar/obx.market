<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

class OBX_Test_OrderPropertyValues extends OBX_Market_TestCase
{
	public function testPrepareTests() {
		require_once dirname(__FILE__)."OrderProperty.php";
		$Test_OrderProperty = new OBX_Test_OrderProperty();
		$Test_OrderProperty->testPrepareTests();
		$Test_OrderProperty->testAddProperty();
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
}