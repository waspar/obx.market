<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

include dirname(__FILE__).'/_Basket.php';

final class OBX_BasketItemList extends OBX_Test_Lib_Basket
{
	public function testGetTestVisitor() {
		return $this->_getTestVisitor();
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
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAddTestOrder() {
		return $this->_addTestOrder();
	}


	/**
	 * Этот тест должег обработать ошибку на принадлежность инфоблока к Торговым каталогам
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 * @depends testCreateNotECommerceIBlock
	 */
	public function testAddNotAProduct() {
		$newBasketItemID = self::$_BasketItemDBS->add(array(
			'VISITOR_ID' => self::$_Visitor->getFields('ID'),
			'PRODUCT_ID' => self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0],
			'QUANTITY' => rand(1, 9),
			'PRICE_ID' => self::$_arPrice['ID'],
			'PRICE_VALUE' => '100',
			'DISCOUNT_VALUE' => '0'
		));
		$arError = self::$_BasketItemDBS->popLastError('ARRAY');
		$this->assertGreaterThan(0, $newBasketItemID);
		// Обрабатываем ошибку. Нельзя добавить в корзину элемент из инфоблока не являющегося торговым каталогом
		$this->assertEquals(9, $arError['CODE']);
	}

	/**
	 * Добавления товаров в корзину
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAdd4Visitor() {
		foreach(self::$_arPoductList as $arElement) {
			$arOptimalPrice = self::$_PriceDBS->getOptimalProductPrice($arElement['ID']);
			$this->assertNotEmpty($arOptimalPrice, 'Error: can\'t get optimal price of product. Check price permissions');
			$newBasketITemID = self::$_BasketItemDBS->add(array(
				'VISITOR_ID' => self::$_Visitor->getFields('ID'),
				'PRODUCT_ID' => $arElement['ID'],
				'QUANTITY' => rand(1, 9),
				'PRICE_ID' => $arOptimalPrice['PRICE_ID'],
				'PRICE_VALUE' => $arOptimalPrice['VALUE'],
				'DISCOUNT_VALUE' => $arOptimalPrice['DISCONT_VALUE']
			));
			if($newBasketITemID < 1) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				// error code = 6 если товар в БД корзины уже есть данный товара. Допустимо. Обрабатываем.
				if($arError['CODE'] != 6) {
					$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
				}
			}
		}
	}

	/**
	 * @depends testAddTestOrder
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAddToOrder() {
		foreach(self::$_arPoductList as $arElement) {
			$arOptimalPrice = self::$_PriceDBS->getOptimalProductPrice($arElement['ID']);
			$this->assertNotEmpty($arOptimalPrice, 'Error: can\'t get optimal price of product. Check price permissions');
			$newBasketITemID = self::$_BasketItemDBS->add(array(
				'ORDER_ID' => self::$_arTestOrder['ID'],
				// В след. строке мы моделируем ситуацию когда товар из корзины стал товаром заказа
				'VISITOR_ID' => (rand(0,1)?null:self::$_Visitor->getFields('ID')),
				'PRODUCT_ID' => $arElement['ID'],
				'QUANTITY' => rand(1, 9),
				'PRICE_ID' => $arOptimalPrice['PRICE_ID'],
				'PRICE_VALUE' => $arOptimalPrice['VALUE'],
				'DISCOUNT_VALUE' => $arOptimalPrice['DISCONT_VALUE']
			));
			if($newBasketITemID < 1) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				// error code = 6 если товар в БД корзины уже есть данный товара. Допустимо. Обрабатываем.
				if($arError['CODE'] != 6) {
					$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
				}
			}
		}
	}

	/**
	 * Получаем заказы привязанные к постетителю
	 * В выборку могут попасть товары уже привязанные к товару
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testGetListFromVisitor() {
		$arVisitorBasket = self::$_BasketItemDBS->getListArray(null, array('VISITOR_ID' => self::$_Visitor->getFields('ID')));
		$this->assertNotEmpty($arVisitorBasket, 'Error: Visitor basket is empty');
		$this->assertGreaterThan(19, count($arVisitorBasket), 'Error: in visitor basket less then 20 product positions');
		foreach($arVisitorBasket as &$arItem) {
			$this->assertArrayHasKey('ORDER_ID', $arItem);
			$this->assertArrayHasKey('VISITOR_ID', $arItem);
			$this->assertArrayHasKey('QUANTITY', $arItem);
			$this->assertArrayHasKey('PRICE_VALUE', $arItem);
			$this->assertNotEmpty($arItem['VISITOR_ID']);
			$this->assertGreaterThan(0, $arItem['QUANTITY']);
			$this->assertGreaterThan(0, $arItem['PRICE_VALUE']);
		} unset($arItem);
	}

	/**
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 * @depends testAddToOrder
	 */
	public function testGetListFromOrder() {
		$arVisitorBasket = self::$_BasketItemDBS->getListArray(null, array('ORDER_ID' => self::$_arTestOrder['ID']));
		$this->assertNotEmpty($arVisitorBasket, 'Error: Order basket is empty');
		$this->assertGreaterThan(19, count($arVisitorBasket), 'Error: in visitor basket less then 20 product positions');
		foreach($arVisitorBasket as &$arItem) {
			$this->assertArrayHasKey('ORDER_ID', $arItem);
			$this->assertArrayHasKey('VISITOR_ID', $arItem);
			$this->assertArrayHasKey('QUANTITY', $arItem);
			$this->assertArrayHasKey('PRICE_VALUE', $arItem);
			$this->assertNotEmpty($arItem['ORDER_ID']);
			$this->assertGreaterThan(0, $arItem['ORDER_ID']);
			$this->assertGreaterThan(0, $arItem['QUANTITY']);
			$this->assertGreaterThan(0, $arItem['PRICE_VALUE']);
		} unset($arItem);
	}

	/**
	 * Получаем содержимое корзины
	 * TODO: Что бы получить только корзину без товаров заказа, надо дописать в DBSimple логику OR и возможность  проверять на is null и nit is null
	 */
	public function testGetListOnlyFromBasket() {
		$arVisitorBasket = self::$_BasketItemDBS->getListArray(null, array(
			'VISITOR_ID' => self::$_Visitor->getFields('ID'),
			'ORDER_ID' => 'null'
		));
		$this->assertNotEmpty($arVisitorBasket, 'Error: Visitor basket is empty');
		$this->assertGreaterThan(19, count($arVisitorBasket), 'Error: in visitor basket less then 20 product positions');
		foreach($arVisitorBasket as &$arItem) {
			$this->assertArrayHasKey('ORDER_ID', $arItem);
			$this->assertArrayHasKey('VISITOR_ID', $arItem);
			$this->assertArrayHasKey('QUANTITY', $arItem);
			$this->assertArrayHasKey('PRICE_VALUE', $arItem);
			$this->assertEmpty($arItem['ORDER_ID']);
			$this->assertNotEmpty($arItem['VISITOR_ID']);
			$this->assertGreaterThan(0, $arItem['QUANTITY']);
			$this->assertGreaterThan(0, $arItem['PRICE_VALUE']);
		} unset($arItem);
	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testUpdate() {

	}

	/**
	 * Этот тест должен обработать ошибку, поскуольку нельщя обновить саму позицию.
	 * Если необходимо сменить товарв в корзине, то необходимо старый товар удалить и новый добавить
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testTryToUpdateProductID() {

	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testDeleteFromVisitor() {

	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testDeleteFromOrder() {

	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testSimpleDeletion() {

	}
}
