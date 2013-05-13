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
	public function testGetNullValues() {
		require_once dirname(__FILE__).'/Basket.php';
		$TestCase = new OBX_Test_Basket;
		$TestCase->setTestResultObject($this->getTestResultObject());
		$TestCase->setName('testAuthUser');
		$TestCase->runTest();
		$resO = $TestCase->getTestResultObject();
		$res = $TestCase->getResult();
		$debug=1;
		$this->assertTrue(true);
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