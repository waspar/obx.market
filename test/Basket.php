<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\Basket;
use OBX\Market\CurrencyInfo;

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
	public static function getBaskets() {
		return array(
			array('USER_BASKET'),
			array('ANON_BASKET'),
			array('ORDER_BASKET')
		);
	}

	public function testGetTestUser(){
		$this->_getTestUser();
	}

	public function testAuthUser() {
		global $USER;
		$USER->Authorize(self::$_arSomeOtherTestUser['ID']);
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
		self::$_BasketArray['USER_BASKET'] = $Basket;
		$Basket->clear();
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
		$Basket->clear();
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
		$Basket->clear();
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
		$arErrorBefore = $Basket->popLastError('ARRAY');
		// Обрабатываем ошибку. Нельзя добавить в корзину элемент из инфоблока не являющегося торговым каталогом
		$this->assertEquals(5, $arError['CODE'], 'Error: returned not expected error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		$text = $arErrorBefore['TEXT']."\n";
		$text .= GetMessage('OBX_MARKET_TEST_BASKET_ERROR_11_310');
		$this->assertEquals(309, $arErrorBefore['CODE'], 'Error: returned not expected error: '.$text.'; code: '.$arErrorBefore['CODE']);
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
		// +++ именно меньше нуля, поскольку при возникновении ошибки будет возвращено значение -1
		// возвращать нуль не совсем корректно ибо по смыслу кол-во товара может быть равным 0 :)
		$this->assertLessThan(0, $newQuantity, GetMessage('OBX_MARKET_TEST_BASKET_ERROR_71'));
		// ^^^
		$arError = $Basket->popLastError('ARRAY');
		$arErrorBefore = $Basket->popLastError('ARRAY');
		// +++ Обрабатываем ошибку. Цена не указана явно и получить её из элемента не удастся. Код ошибки 10
		$this->assertEquals(5, $arError['CODE'], 'Error: returned not expected error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		// ^^^
		$this->assertEquals(310, $arErrorBefore['CODE'], 'Error: returned not expected error: '.$arErrorBefore['TEXT'].'; code: '.$arErrorBefore['CODE']);
	}

	public function testAddProductWithExplicitPriceValueZero() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['ANON_BASKET'];
		$newQuantity = $Basket->addProduct(self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0], 1, 0);
		$this->assertEquals(1, $newQuantity);
		$bSuccess = $Basket->removeProduct(self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0]);
		$this->assertTrue($bSuccess);
	}

	/**
	 * @depends testMoveNotECommerceIBlock2ECommerceState
	 */
	public function testMoveNotECommerceIBlockStateBack() {
		$this->_moveNotECommerceIBlockStateBack();
	}


	/**
	 * Добавление товара в корзину
	 * @param string $basketName
	 * @depends testGetCurrentBasketFromAuthUser
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @dataProvider getBaskets
	 */
	public function testAddItems2Basket($basketName) {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray[$basketName];
		$addQuantity = rand(1, 9);
		foreach(self::$_arPoductList as &$arElement) {
			$newQuantity = $Basket->addProduct($arElement['ID'], $addQuantity);
			if( $newQuantity < 0 ) {
				$arError = $Basket->popLastError('ARRAY');
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
			$this->assertGreaterThanOrEqual($addQuantity, $newQuantity);
		}
	}

	/**
	 * @dataProvider getBaskets
	 * @depends testAddItems2Basket
	 * @param string $basketName
	 * @param int $expectProductCount
	 */
	public function testGetBasketData($basketName, $expectProductCount = null) {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray[$basketName];
		if($expectProductCount===null) $expectProductCount = 20;
		$this->assertFalse($Basket->isEmpty());
		$this->assertGreaterThan(0, $Basket->getCost());
		$this->assertGreaterThanOrEqual($expectProductCount, $Basket->getProductsCount());
		$this->assertNull($Basket->getProductCost(686868686868));
		$this->assertEquals(0, $Basket->getProductQuantity(686868686868));
		$this->assertTrue($Basket->isEmpty(68686868686868));

		$arItemsListRaw = self::$_BasketItemDBS->getListArray(null, array(
			'BASKET_ID' => $Basket->getFields('ID')
		));
		$arItemsList = \OBX_Tools::getListIndex($arItemsListRaw, 'PRODUCT_ID', true, true);
		$this->assertNotEmpty($arItemsList);

		$dbCheckBasketCost = 0;
		foreach(self::$_arPoductList as &$arIBElement) {
			if( array_key_exists('__DELETED', $arIBElement) ) {
				continue;
			}
			$quantity = $Basket->getProductQuantity($arIBElement['ID']);
			$isEmpty = $Basket->isEmpty($arIBElement['ID']);
			$productCost = $Basket->getProductCost($arIBElement['ID']);
			$this->assertFalse($isEmpty);
			$this->assertGreaterThan(0, $quantity);
			$this->assertGreaterThan(0, $productCost);
			// теперь сравним с содержимым БД
			$this->assertArrayHasKey($arIBElement['ID'], $arItemsList);
			$this->assertArrayHasKey('QUANTITY', $arItemsList[$arIBElement['ID']]);
			$this->assertEquals($arItemsList[$arIBElement['ID']]['QUANTITY'], $quantity);
			$itemCostDB = floatVal($arItemsList[$arIBElement['ID']]['QUANTITY']) * floatVal($arItemsList[$arIBElement['ID']]['PRICE_VALUE']);
			$dbCheckBasketCost += $itemCostDB;
			$this->assertEquals($itemCostDB, $productCost);
		}
		$this->assertEquals($dbCheckBasketCost, $Basket->getCost());
		$arCurrency = CurrencyInfo::getInstance($Basket->getFields('CURRENCY'))->getFields();
		$formatString = $arCurrency['FORMAT'][LANGUAGE_ID]['FORMAT'];
		$checkString = str_replace(array('#',' ', '.'), '', $formatString);
		$this->assertTrue( (strpos($Basket->getCost(true), $checkString) !==false) );
	}


	/**
	 * @dataProvider getBaskets
	 * @param string $basketName
	 * @depends testGetBasketData
	 */
	public function testUpdateBasketItems($basketName) {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray[$basketName];
		foreach(self::$_arPoductList as $arIBElement) {
			$Basket->setProductPriceValue($arIBElement['ID'], 268);
			$Basket->setProductQuantity($arIBElement['ID'], 68);
		}
		$this->testGetBasketData($basketName);
	}

	/**
	 * @param string $basketName
	 * @dataProvider getBaskets
	 * @depends testGetBasketData
	 * @depends testTryToAddProductWithoutPrice
	 */
	public function testDeleteSomeOfItem($basketName) {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray[$basketName];
		$iItem = 0;
		foreach(self::$_arPoductList as &$arIBElement) {
			$iItem++;
			if($iItem == 6) {
				break;
			}
			$bSuccess = $Basket->removeProduct($arIBElement['ID']);
			if(!$bSuccess) {
				$arError = $Basket->popLastError('ARRAY');
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
			$arIBElement['__DELETED'] = true;
		}
		$prodCount = $Basket->getProductsCount();
		// +++ [pronix:2013-06-02]
		// Здесь стабильно вылетает 16 если хотя бы раз
		// после чистого запуска не сразуботал тест
		// testTryToAddProductWithoutPrice или testTryToAddNotAProduct
		// еслибыл пропущен товар без цены или элемент не торгового каталога,
		// значит в корзине на один товар больше.
		// Актуальное значение будет не 15, а 16
		// потому добавлена зависимость
		// ^^^ [pronix:2013-06-02]
		$this->assertEquals(15, $prodCount);
		$this->testGetBasketData($basketName, 15);
	}

	public function testClearBasket() {
		/**
		 * @var Basket $Basket
		 */
		$Basket = &self::$_BasketArray['ANON_BASKET'];
		$bSuccess = $Basket->clear();
		$this->assertTrue($bSuccess);
		$this->assertTrue($Basket->isEmpty());
		$this->assertEquals(0, $Basket->getProductsCount());
		$this->assertEquals(0, $Basket->getCost());
		foreach(self::$_arPoductList as &$arIBElement) {
			$this->assertTrue($Basket->isEmpty($arIBElement['ID']));
			$this->assertEquals(0, $Basket->getProductCost($arIBElement['ID']));
			$this->assertEquals(0, $Basket->getProductQuantity($arIBElement['ID']));
		}
	}

	/**
	 * @depends testClearBasket
	 */
	public function testMergeBaskets() {
		/**
		 * @var Basket $AnonBasket
		 * @var Basket $UserBasket
		 */
		$AnonBasket = &self::$_BasketArray['ANON_BASKET'];
		$UserBasket = &self::$_BasketArray['USER_BASKET'];
		$AnonBasket->mergeBasket($UserBasket);
		$arAnonBasketItems = $AnonBasket->getProductsList(true);
		$this->assertNotEmpty($arAnonBasketItems);
		$arUserBasketItems = $UserBasket->getProductsList(true);
		foreach($arUserBasketItems as $productID => &$arUserBasketItem) {
			$this->assertEquals($arUserBasketItem['PRODUCT_ID'], $arAnonBasketItems[$productID]['PRODUCT_ID']);
			$this->assertEquals($arUserBasketItem['QUANTITY'], $arAnonBasketItems[$productID]['QUANTITY']);
			$this->assertEquals($arUserBasketItem['PRICE_VALUE'], $arAnonBasketItems[$productID]['PRICE_VALUE']);
		}
		// clear after merge
		$UserBasket->clear();
		$this->assertTrue($UserBasket->isEmpty());
		$UserBasket->mergeBasket($AnonBasket, true);
		$this->assertFalse($UserBasket->isEmpty());
		$this->assertTrue($AnonBasket->isEmpty());
		$arAnonBasketItems = $AnonBasket->getProductsList(true);
		$arUserBasketItems = $UserBasket->getProductsList(true);
		$this->assertEmpty($arAnonBasketItems);
		$this->assertNotEmpty($arUserBasketItems);

		// merge to anonBasket back
		$AnonBasket->mergeBasket($UserBasket);
		$this->assertFalse($AnonBasket->isEmpty());
	}

	public function testNotALink() {
		/**
		 * @var Basket $AnonBasket
		 * @var Basket $UserBasket
		 */
		$AnonBasket = &self::$_BasketArray['ANON_BASKET'];
		$arProductList = $AnonBasket->getProductsList(true);
		foreach($arProductList as &$arItem) {
			$AnonBasket->setProductQuantity($arItem['PRODUCT_ID'], 68);
			$arItem['QUANTITY'] = 67;
		}
		$arProductList1 = $AnonBasket->getProductsList(true);
		foreach($arProductList1 as $key => &$arItem) {
			$this->assertEquals(68, $arItem['QUANTITY']);
		}
	}
}
