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
		global $USER;
		$Basket = Basket::getCurrent();
		$this->assertGreaterThan(0, $Basket->getFields('ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_1'));
		$this->assertGreaterThan(0, $Basket->getFields('USER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_2'));
		$this->assertEquals($USER->GetID(), $Basket->getFields('USER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_21'));
		$this->assertNull($Basket->getFields('ORDER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_3'));
		$this->assertNull($Basket->getFields('HASH_STRING'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_4'));
		self::$_BasketArray['CURRENT_USER'] = $Basket;
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
		$this->assertGreaterThan(0, $Basket->getFields('ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_1'));
		$this->assertEquals(32, strlen($Basket->getFields('HASH_STRING')), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_5'));
		$this->assertNull($Basket->getFields('USER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_6'));
		$this->assertNull($Basket->getFields('ORDER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_3'));
		self::$_BasketArray['ANON_BASKET'] = $Basket;
	}

	public function testGetTestUser(){
		$this->_getTestUser();
	}

	/**
	 * @depends testGetTestUser
	 */
	public function testAddTestOrder() {
		$this->_addTestOrder();
		$this->_addSomeOtherTestOrder();
	}

	/**
	 * @depends testAddTestOrder
	 */
	public function testGetBasketFromOrder() {
		$Basket = Basket::getByOrderID(self::$_arTestOrder['ID']);
		$this->assertGreaterThan(0, $Basket->getFields('ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_1'));
		$this->assertNull($Basket->getFields('HASH_STRING'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_8'));
		$this->assertNull($Basket->getFields('USER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_9'));
		$this->assertGreaterThan(0, $Basket->getFields('ORDER_ID'), GetMessage('OBX_MARKET_TEST_BASKET_ERROR_10'));
		self::$_BasketArray['ORDER_BASKET'] = $Basket;
	}



	public function testAddTestPrice() {
		return $this->_addTestPrice();
	}

	/**
	 * @depends testAddTestPrice
	 */
	public function testGetTestIBlockData() {
		return $this->_getTestIBlockData();
	}

	public function testCreateNotECommerceIBlock() {
		return $this->_createNotECommerceIBlock();
	}


	/**
	 * Этот тест должег обработать ошибку на принадлежность инфоблока к Торговым каталогам
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 * @depends testCreateNotECommerceIBlock
	 */
	public function testTryToAddNotAProduct() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['ANON_BASKET'];
		$newQuantity = $Basket->addProduct(self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0]);
		$this->assertLessThan(0, $newQuantity, GetMessage('OBX_MARKET_TEST_BASKET_ERROR_7'));
		$arError = $Basket->popLastError('ARRAY');
		// Обрабатываем ошибку. Нельзя добавить в корзину элемент из инфоблока не являющегося торговым каталогом
		$this->assertEquals(309, $arError['CODE'], 'Error: returned not expected error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
	}

	/**
	 * @depends testTryToAddNotAProduct
	 */
	public function testMoveNotECommerceIBlock2ECommerceState() {
		$this->_moveNotECommerceIBlock2ECommerceState();
	}

	/**
	 * Тест обрабатывает ошибку: нельзя добавить товар без цены
	 * @depends testTryToAddNotAProduct
	 * @depends testMoveNotECommerceIBlock2ECommerceState
	 */
	public function testTryToAddProductWithoutPrice() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['ANON_BASKET'];
		$newQuantity = $Basket->addProduct(self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0]);
		$this->assertLessThan(0, $newQuantity, $bSuccess, GetMessage('OBX_MARKET_TEST_BASKET_ERROR_71'));
		$arError = $Basket->popLastError('ARRAY');
		// Обрабатываем ошибку. Цена не указана явно и получить её из элемента не удастся. Код ошибки 10
		$this->assertEquals(310, $arError['CODE'], 'Error: returned not expected error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
	}

	/**
	 * @depends testMoveNotECommerceIBlock2ECommerceState
	 */
	public function testMoveNotECommerceIBlockStateBack() {
		$this->_moveNotECommerceIBlockStateBack();
	}


	/**
	 * Добавление товара в корзину пользвоателя
	 * @depends testGetCurrentBasketFromAuthUser
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAddItems2UserBasket() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['CURRENT_USER'];
		$addQuantity = rand(1, 9);
		foreach(self::$_arPoductList as $arElement) {
			$newQuantity = $Basket->addProduct($arElement['ID'], $addQuantity);
			if( $newQuantity < 0 ) {
				$arError = $Basket->popLastError('ARRAY');
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
			$this->assertGreaterThanOrEqual($addQuantity, $newQuantity);

		}
	}

	/**
	 * Добавление товара в корзину посетителя
	 * @depends testGetCurrentBasketFromCookieHash
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAddItems2AnonBasket() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['ANON_BASKET'];
		$addQuantity = rand(0, 9);
		foreach(self::$_arPoductList as $arElement) {
			$newQuantity = $Basket->addProduct($arElement['ID'], $addQuantity);
			if( $newQuantity < 0 ) {
				$arError = $Basket->popLastError('ARRAY');
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
			$this->assertGreaterThanOrEqual($addQuantity, $newQuantity);

		}
	}

	/**
	 * Добавления товаров в корзину
	 * @depends testGetBasketFromOrder
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAddItems2Order() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['ORDER_BASKET'];
		$addQuantity = rand(0, 9);
		foreach(self::$_arPoductList as $arElement) {
			$newQuantity = $Basket->addProduct($arElement['ID'], $addQuantity);
			if( $newQuantity < 0 ) {
				$arError = $Basket->popLastError('ARRAY');
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
			$this->assertGreaterThanOrEqual($addQuantity, $newQuantity);

		}
	}

	public function testGetUserBasketItems() {

	}

	public function testGetAnonBasketItems() {

	}

	public function testGetOrderItems() {

	}


	public function testUpdateItems() {

	}

	public function testMergeBasket() {

	}
}
