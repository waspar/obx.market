<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\Basket;

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
	}

	/**
	 * @depends testAuthUser
	 */
	public function testGetCurrentBasketFromAuthUser() {
		$Basket = Basket::getCurrent();
		$this->assertGreaterThan(0, $Basket->getFields('ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_1'));
		$this->assertGreaterThan(0, $Basket->getFields('USER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_2'));
		$this->assertNull($Basket->getFields('ORDER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_3'));
		$this->assertNull($Basket->getFields('HASH_STRING'), 'Error: user basket ');
	}

	public function testLogoutUser() {
		global $USER;
		$USER->Logout();
	}

	/**
	 * @depends testLogoutUser
	 */
	public function testGetCurrentBasketFromCookieHash() {
		$Basket = Basket::getCurrent();
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
