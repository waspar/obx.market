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

require_once dirname(__FILE__).'/_Basket.php';

/**
 * Тест близок по структуре к OBX_BasketItemList
 * с тем лишь отличием, что тут мы тестируем обертку над OBX\BasketItem ,
 * которая автоматизирует работу с Visitors
 * Class OBX_Test_BasketItem
 */
final class OBX_Test_Basket extends OBX_Test_Lib_Basket
{
	public function testAuthUser() {
		global $USER;
		$USER->Authorize(1);
		$this->assertTrue(false);
		return -123;
	}

	/**
	 * @depends testAuthUser
	 */
	public function testGetCurrentBasketFromAuthUser() {
		$Basket = OBX_Basket::getCurrent();
	}

	public function testLogoutUser() {
		global $USER;
		$USER->Logout();
	}

	/**
	 * @depends testLogoutUser
	 */
	public function testGetCurrentBasketFromCookieHash() {
		$Basket = OBX_Basket::getCurrent();
	}

	public function testAddItems2Basket() {

	}

	public function testAuthorizeUser() {

	}

	public function testAddTestOrder() {

	}

	public function testAddItems2Order() {

	}

	public function testGetBasketItems() {

	}

	public function testGetOrderItems() {

	}


	public function testUpdateItems() {

	}

	public function testMergeBasket() {

	}
}
