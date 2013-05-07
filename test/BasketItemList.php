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
	public function testTryToAddNotAProduct() {
		$newBasketItemID = self::$_BasketItemDBS->add(array(
			'VISITOR_ID' => self::$_Visitor->getFields('ID'),
			'PRODUCT_ID' => self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0],
			'QUANTITY' => rand(1, 9),
			'PRICE_ID' => self::$_arPrice['ID'],
			'PRICE_VALUE' => '100',
			'DISCOUNT_VALUE' => '0'
		));
		$arError = self::$_BasketItemDBS->popLastError('ARRAY');
		$this->assertEquals(0, $newBasketItemID);
		// Обрабатываем ошибку. Нельзя добавить в корзину элемент из инфоблока не являющегося торговым каталогом
		$this->assertEquals(9, $arError['CODE']);
	}

	/**
	 * @depends testTryToAddNotAProduct
	 */
	public function testMoveNotECommerceIBlock2ECommerceState() {
		$this->_moveNotECommerceIBlock2ECommerceState();
	}

	/**
	 * @depends testTryToAddNotAProduct
	 * @depends testMoveNotECommerceIBlock2ECommerceState
	 */
	public function testTryToAddProductWithoutPrice() {
		$newBasketItemID = self::$_BasketItemDBS->add(array(
			'VISITOR_ID' => self::$_Visitor->getFields('ID'),
			'PRODUCT_ID' => self::$_arTestNotEComIBlock['__ELEMENTS_ID_LIST'][0],
			'QUANTITY' => rand(1, 9),
			'PRICE_ID' => self::$_arPrice['ID'],
		));
		$arError = self::$_BasketItemDBS->popLastError('ARRAY');
		$this->assertEquals(0, $newBasketItemID);
		// Обрабатываем ошибку. Цена не указана явно и получить её из элемента не удастся. Код ошибки 10
		$this->assertEquals(10, $arError['CODE'], 'Error: returned not expected error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
	}

	/**
	 * @depends testMoveNotECommerceIBlock2ECommerceState
	 */
	public function testMoveNotECommerceIBlockStateBack() {
		$this->_moveNotECommerceIBlockStateBack();
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

	public function testNewDBSimpleGetListFilter() {
		$arVisitors[0] = new OBX_Visitor(array('COOKIE_ID' => md5('__TEST_VISITOR_0__')));
		$arVisitors[1] = new OBX_Visitor(array('COOKIE_ID' => md5('__TEST_VISITOR_1__')));
		$arVisitors[2] = new OBX_Visitor(array('COOKIE_ID' => md5('__TEST_VISITOR_2__')));
		$arVisitors[3] = new OBX_Visitor(array('COOKIE_ID' => md5('__TEST_VISITOR_3__')));
		$arVisitors[4] = new OBX_Visitor(array('COOKIE_ID' => md5('__TEST_VISITOR_4__')));
		$arVisitors[0] = $arVisitors[0]->getFields('ID');
		$arVisitors[1] = $arVisitors[1]->getFields('ID');
		$arVisitors[2] = $arVisitors[2]->getFields('ID');
		$arVisitors[3] = $arVisitors[3]->getFields('ID');
		$arVisitors[4] = $arVisitors[4]->getFields('ID');
		$rsVisitorBasket = self::$_BasketItemDBS->getList(null, array(
			'VISITOR_ID' => array(
				$arVisitors[0],
				$arVisitors[1],
				$arVisitors[2],
				$arVisitors[3],
				$arVisitors[4],
			),
			'!VISITOR_ID' => array(
				$arVisitors[0],
				$arVisitors[1],
				$arVisitors[2],
				$arVisitors[3],
				$arVisitors[4],
			),
			'ORDER_ID' => null,
			'!ORDER_ID' => array(
				null, '1', false, '2',
			),
			'OR' => array(
				array(
					'>PRICE_VALUE' => '10.32',
					'<PRICE_VALUE' => '1000.46',
				),
				array(
					'<DISCOUNT_VALUE' => '0.10',
					'>=DISCOUNT_VALUE' => '0.00'
				)
			),
			'OR_1' => array(
				array(
					'!VAT_ID' => null,
					'<VAT_VALUE' => '30.46'
				)
			),
			'OR_2' => array(
				array(
					'!VAT_ID' => null,
					'<VAT_VALUE' => '30.46'
				),
				array(
					'VAT_ID' => false,
					'PRICE_VALUE' => 100
				)
			)
		));
		$lastQueryString = self::$_BasketItemDBS->getLastQueryString();
		$lastQueryString = str_replace(array("  ", "\n", "\t"), ' ', $lastQueryString);
		$lastQueryString = str_replace('  ', ' ', $lastQueryString);
		$lastQueryString = str_replace('  ', ' ', $lastQueryString);
		$lastQueryString = str_replace('  ', ' ', $lastQueryString);
		$lastQueryString = str_replace('  ', ' ', $lastQueryString);
		$this->assertEquals(<<<SQL
SELECT I.ID AS ID, I.ORDER_ID AS ORDER_ID, V.ID AS VISITOR_ID, I.PRODUCT_ID AS PRODUCT_ID, I.PRODUCT_NAME AS PRODUCT_NAME, I.QUANTITY AS QUANTITY, I.WEIGHT AS WEIGHT, I.PRICE_ID AS PRICE_ID, I.PRICE_VALUE AS PRICE_VALUE FROM obx_basket_items AS I LEFT JOIN obx_visitors AS V ON (V.ID = I.VISITOR_ID) WHERE (1=1) AND ( V.ID = '19' OR V.ID = '20' OR V.ID = '21' OR V.ID = '22' OR V.ID = '23' ) AND ( V.ID <> '19' AND V.ID <> '20' AND V.ID <> '21' AND V.ID <> '22' AND V.ID <> '23' ) AND (I.ORDER_ID IS NULL) AND ( I.ORDER_ID IS NOT NULL AND I.ORDER_ID <> '1' AND I.ORDER_ID <> '' AND I.ORDER_ID <> '2' ) AND ((1<>1) OR (I.PRICE_VALUE > '10.32') OR (I.PRICE_VALUE < '1000.46') ) AND ((1<>1) OR (I.DISCOUNT_VALUE < '0.10') OR (I.DISCOUNT_VALUE >= '0.00') ) AND ((1<>1) OR (I.VAT_ID IS NOT NULL) OR (I.VAT_VALUE < '30.46') ) AND ((1<>1) OR (I.VAT_ID IS NOT NULL) OR (I.VAT_VALUE < '30.46') ) AND ((1<>1) OR (I.VAT_ID = '') OR (I.PRICE_VALUE = '100') ) ORDER BY I.ID ASC
SQL
		, $lastQueryString);
	}

	/**
	 * Получаем содержимое корзины
	 */
	public function testGetListOnlyFromBasket() {
		$arVisitorBasket = self::$_BasketItemDBS->getListArray(null, array(
			'VISITOR_ID' => self::$_Visitor->getFields('ID'),
			'ORDER_ID' => null
		));
		//$lastSQL = self::$_BasketItemDBS->getLastQueryString();
		$this->assertNotEmpty($arVisitorBasket, 'Error: Visitor basket is empty');
		$this->assertGreaterThan(19, count($arVisitorBasket), 'Error: in visitor basket less then 20 product positions');
		foreach($arVisitorBasket as &$arItem) {
			$this->assertArrayHasKey('ORDER_ID', $arItem);
			$this->assertArrayHasKey('VISITOR_ID', $arItem);
			$this->assertArrayHasKey('QUANTITY', $arItem);
			$this->assertArrayHasKey('PRICE_VALUE', $arItem);
			$this->assertEmpty($arItem['ORDER_ID']);
			$this->assertTrue($arItem['ORDER_ID']===null);
			$this->assertNotEmpty($arItem['VISITOR_ID']);
			$this->assertGreaterThan(0, $arItem['QUANTITY']);
			$this->assertGreaterThan(0, $arItem['PRICE_VALUE']);
		} unset($arItem);
	}

	/**
	 * TODO: Просто обновляем любое поле заказа. Проверяем через getList что оно поменялось
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testUpdate() {

	}

	/**
	 * Этот тест должен обработать ошибку, поскуольку нельзя обновить саму позицию.
	 * Если необходимо сменить товарв в корзине, то необходимо старый товар удалить и новый добавить
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testTryToUpdateProductID() {

	}

	/**
	 * Простое удаление по вервичному ключу
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testSimpleDeletion() {

	}

	/**
	 * Удаляем заказы привязанные к посетителю.
	 * Товары могут быть привязаны к заказу. Не имеет значения. Удаляем по связке с посетителем
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAdd4Visitor
	 */
	public function testDeleteFromVisitor() {

	}

	/**
	 * Удаляем товары привязанные к тестовому заказу
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testDeleteFromOrder() {

	}

	/**
	 * Удаляем заказы привязанные к корзине.
	 * Варианта два. Или получить список через arFilter(array( 'ORDER_ID' => null))
	 * или добавить в OBX\DBSimple в метод deleteByFilter() поддержку проверки на IS NULL так же как это сделано в getList
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAdd4Visitor
	 */
	public function testDeleteFromBasket() {

	}
}
