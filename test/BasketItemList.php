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
use OBX\Market\BasketDBS;

require_once dirname(__FILE__).'/_Basket.php';

final class OBX_Test_BasketItemList extends OBX_Test_Lib_Basket
{

	/**
	 * @var array
	 * $_arTestBasketItems = array(
	 * 		'BASKET' => array(
	 * 			$BASKET_ID => array($ITEMS_LIST)
	 * 			...
	 * 		)
	 * 		'ORDER' => array(
	 * 			$ORDER_ID => array($ITEMS_LIST)
	 * 		)
	 * )
	 */
	static public $_arTestBasketItems = array(
		'BASKET' => array(),
		'ORDER' => array()
	);

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

	public function testGetTestBasket() {
		$this->_getTestBasket();
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
		$newBasketItemID = self::$_BasketItemDBS->add(array(
			'BASKET_ID' => self::$_BasketArray['TEST_BASKET']->getFields('ID'),
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
			'BASKET_ID' => self::$_BasketArray['TEST_BASKET']->getFields('ID'),
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
	 * @depends testGetTestBasket
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAdd2Basket() {
		foreach(self::$_arPoductList as $arElement) {
			$arOptimalPrice = self::$_PriceDBS->getOptimalProductPrice($arElement['ID']);
			$this->assertNotEmpty($arOptimalPrice, 'Error: can\'t get optimal price of product. Check price permissions');
			$newBasketITemID = self::$_BasketItemDBS->add(array(
				'BASKET_ID' => self::$_BasketArray['TEST_BASKET']->getFields('ID'),
				'PRODUCT_ID' => $arElement['ID'],
				'QUANTITY' => rand(1, 9),
				'PRICE_ID' => $arOptimalPrice['PRICE_ID'],
				'PRICE_VALUE' => $arOptimalPrice['VALUE'],
				'DISCOUNT_VALUE' => $arOptimalPrice['DISCOUNT_VALUE']
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
				// Не самая простая ситуация, может даже нет смысла её обрабатывать
				//'BASKET_ID' => self::$_BasketArray['TEST_BASKET']->getFields('ID'),
				'PRODUCT_ID' => $arElement['ID'],
				'QUANTITY' => rand(1, 9),
				'PRICE_ID' => $arOptimalPrice['PRICE_ID'],
				'PRICE_VALUE' => $arOptimalPrice['VALUE'],
				'DISCOUNT_VALUE' => $arOptimalPrice['DISCOUNT_VALUE']
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
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 * @depends testAddToOrder
	 */
	public function testGetListFromOrder() {
		$arVisitorBasket = self::$_BasketItemDBS->getListArray(null, array('ORDER_ID' => self::$_arTestOrder['ID']));
		$lastQuery = self::$_BasketItemDBS->getLastQueryString();
		$this->assertNotEmpty($arVisitorBasket, 'Error: Order basket is empty');
		$this->assertGreaterThan(19, count($arVisitorBasket), 'Error: in visitor basket less then 20 product positions');
		foreach($arVisitorBasket as &$arItem) {
			$this->assertArrayHasKey('ORDER_ID', $arItem);
			$this->assertArrayHasKey('BASKET_ID', $arItem);
			$this->assertArrayHasKey('QUANTITY', $arItem);
			$this->assertArrayHasKey('PRICE_VALUE', $arItem);
			$this->assertNotEmpty($arItem['ORDER_ID']);
			$this->assertGreaterThan(0, $arItem['ORDER_ID']);
			$this->assertGreaterThan(0, $arItem['QUANTITY']);
			$this->assertGreaterThan(0, $arItem['PRICE_VALUE']);
			self::$_arTestBasketItems['ORDER'][$arItem['ORDER_ID']][] = $arItem;
		} unset($arItem);
	}

	/**
	 * Получаем содержимое корзины
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testGetTestBasket
	 * @depends testAdd2Basket
	 */
	public function testGetListFromBasket() {
		$arVisitorBasket = self::$_BasketItemDBS->getListArray(null, array(
			'BASKET_ID' => self::$_BasketArray['TEST_BASKET']->getFields('ID'),
			'ORDER_ID' => null
		));
		$lastQuery = self::$_BasketItemDBS->getLastQueryString();
		$this->assertNotEmpty($arVisitorBasket, 'Error: Visitor basket is empty');
		$this->assertGreaterThan(19, count($arVisitorBasket), 'Error: in visitor basket less then 20 product positions');
		foreach($arVisitorBasket as &$arItem) {
			$this->assertArrayHasKey('ORDER_ID', $arItem);
			$this->assertArrayHasKey('BASKET_ID', $arItem);
			$this->assertArrayHasKey('QUANTITY', $arItem);
			$this->assertArrayHasKey('PRICE_VALUE', $arItem);
			$this->assertEmpty($arItem['ORDER_ID']);
			$this->assertTrue($arItem['ORDER_ID']===null);
			$this->assertNotEmpty($arItem['BASKET_ID']);
			$this->assertGreaterThan(0, $arItem['QUANTITY']);
			$this->assertGreaterThan(0, $arItem['PRICE_VALUE']);
			self::$_arTestBasketItems['BASKET'][$arItem['BASKET_ID']][] = $arItem;
		} unset($arItem);
	}

	/**
	 * @depends testGetListFromBasket
	 */
	public function testUpdate() {
		$arBasketItemBeforeUpdate = &self::$_arTestBasketItems['BASKET'][self::$_BasketArray['TEST_BASKET']->getFields('ID')][0];
		$bSuccess = self::$_BasketItemDBS->update(array(
			'ID' => $arBasketItemBeforeUpdate['ID'],
			'DISCOUNT_VALUE' => 686868.68
		));
		if( !$bSuccess ) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arUpdatedBasketItem = self::$_BasketItemDBS->getByID($arBasketItemBeforeUpdate['ID']);
		$this->assertNotEmpty($arUpdatedBasketItem, 'Error: test basket item not found');
		$this->assertEquals(686868.68, $arUpdatedBasketItem['DISCOUNT_VALUE'], 'Error: updated field value is wrong.');
		$arBasketItemBeforeUpdate = $arUpdatedBasketItem;
	}

	/**
	 * @depends testGetListFromOrder
	 */
	public function testUpdateOrderItem() {
		$arBasketItemBeforeUpdate = &self::$_arTestBasketItems['ORDER'][self::$_arTestOrder['ID']][0];
		$bSuccess = self::$_BasketItemDBS->update(array(
			'ID' => $arBasketItemBeforeUpdate['ID'],
			'DISCOUNT_VALUE' => 686868.68
		));
		if( !$bSuccess ) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arUpdatedBasketItem = self::$_BasketItemDBS->getByID($arBasketItemBeforeUpdate['ID']);
		$lastQuery = self::$_BasketItemDBS->getLastQueryString();
		$this->assertNotEmpty($arUpdatedBasketItem, 'Error: test basket item not found');
		$this->assertEquals(686868.68, $arUpdatedBasketItem['DISCOUNT_VALUE'], 'Error: updated field value is wrong.');
		$arBasketItemBeforeUpdate = $arUpdatedBasketItem;
	}


	/**
	 * Этот тест должен обработать предупреждение, поскуольку нельзя обновить идентификатор товара в позиции корзины.
	 * Если необходимо сменить товарв в корзине, то необходимо старый товар удалить и новый добавить
	 * @depends testUpdate
	 */
	public function testTryToUpdateProductID() {
		$arBasketItemBeforeUpdate = &self::$_arTestBasketItems['BASKET'][self::$_BasketArray['TEST_BASKET']->getFields('ID')][0];
		$arSomeOtherBasketItem = &self::$_arTestBasketItems['BASKET'][self::$_BasketArray['TEST_BASKET']->getFields('ID')][3];
		$bSuccess = self::$_BasketItemDBS->update(array(
			'ID' => $arBasketItemBeforeUpdate['ID'],
			'DISCOUNT_VALUE' => 686868.68,
			'PRODUCT_ID' => $arSomeOtherBasketItem['PRODUCT_ID']
		));
		if( !$bSuccess ) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arWarning = self::$_BasketItemDBS->popLastWarning('ARRAY');
		if( $arWarning['CODE'] != 1) {
			$this->fail('Error: update() must return error code = 1, but returned code = '.$arWarning['CODE'].'; text: '.$arWarning['TEXT']);
		}
		$this->assertNotEmpty($arWarning, 'Error: update() not returned text of the warning');
	}

	/**
	 * Этот тест должен обработать предупреждение, поскуольку нельзя перемещать позицию между корзинами.
	 * @depends testUpdate
	 * @depends testGetTestBasket
	 */
	public function testTryToUpdateBasketID() {
		$arBasketItemBeforeUpdate = &self::$_arTestBasketItems['BASKET'][self::$_BasketArray['TEST_BASKET']->getFields('ID')][4];
		$SomeOtherBasket = Basket::getByHash(md5('__TEST_AND_DELETE_testTryToUpdateBasketID()'));
		$bSuccess = self::$_BasketItemDBS->update(array(
			'ID' => $arBasketItemBeforeUpdate['ID'],
			'DISCOUNT_VALUE' => 686868.68,
			'QUANTITY' => 68,
			'BASKET_ID' => $SomeOtherBasket->getFields('ID')
		));
		if( !$bSuccess ) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arWarning = self::$_BasketItemDBS->popLastWarning('ARRAY');
		if( $arWarning['CODE'] != 2) {
			$this->fail('Error: update() must return error code = 2, but returned code = '.$arWarning['CODE'].'; text: '.$arWarning['TEXT']);
		}
		$this->assertNotEmpty($arWarning, 'Error: update() not returned text of the warning');
	}

	/**
	 * Простое удаление по вервичному ключу
	 * @depends testGetListFromBasket
	 */
	public function testSimpleDeletion() {
		$basketID = self::$_BasketArray['TEST_BASKET']->getFields('ID');
		for($iItemKey=0; $iItemKey < 5; $iItemKey++) {
			$bSuccess = self::$_BasketItemDBS->delete(self::$_arTestBasketItems['BASKET'][$basketID][$iItemKey]['ID']);
			if( !$bSuccess ) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
			unset(self::$_arTestBasketItems['BASKET'][$basketID][$iItemKey]);
		}
	}

	/**
	 * Удаляем товары по ID-корзины.
	 * Товары могут быть привязаны к заказу. Не имеет значения. Удаляем по связке с посетителем
	 * @depends testGetListFromBasket
	 */
	public function testDeleteFromBasket() {
		$basketID = self::$_BasketArray['TEST_BASKET']->getFields('ID');
		$bSuccess = self::$_BasketItemDBS->deleteByFilter(array('BASKET_ID' => $basketID));
		if( !$bSuccess ) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arBasketItemsAlreadyDeleted = self::$_BasketItemDBS->getListArray(null, array('BASKET_ID' => $basketID));
		$this->assertEmpty($arBasketItemsAlreadyDeleted, 'Error: basket «'.$basketID.'» has items!');
	}

	/**
	 * Удаляем товары привязанные к тестовому заказу
	 * @depends testGetListFromOrder
	 */
	public function testDeleteFromOrder() {
		$orderBasketID = Basket::getByOrderID(self::$_arTestOrder['ID'])->getFields('ID');
		$this->assertGreaterThan(0, $orderBasketID, 'Error: can\'t find order basket');
		$bSuccess = self::$_BasketItemDBS->deleteByFilter(array('BASKET_ID' => $orderBasketID));
		if( !$bSuccess ) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arBasketItemsAlreadyDeleted = self::$_BasketItemDBS->getListArray(null, array('ORDER_ID' => self::$_arTestOrder['ID']));
		$this->assertEmpty($arBasketItemsAlreadyDeleted, 'Error: order basket «'.self::$_arTestOrder['ID'].'» has items!');
	}

	public function testDeleteTestBasket() {
		self::$_BasketDBS->delete(self::$_BasketArray['TEST_BASKET']->getFields('ID'));
		$orderBasketID = Basket::getByOrderID(self::$_arTestOrder['ID'])->getFields('ID');
		self::$_BasketDBS->delete($orderBasketID);
		$someOtherOrderBasketID = Basket::getByOrderID(self::$_arSomeOthTestOrder['ID'])->getFields('ID');
		self::$_BasketDBS->delete($someOtherOrderBasketID);
	}

	/**
	 * Тест новыго фильтра DBSimple
	 */
	public function testNewDBSimpleGetListFilter() {
		$arBaskets[0] = Basket::getByHash(md5('__TEST_VISITOR_0__'));
		$arBaskets[1] = Basket::getByHash(md5('__TEST_VISITOR_1__'));
		$arBaskets[2] = Basket::getByHash(md5('__TEST_VISITOR_2__'));
		$arBaskets[3] = Basket::getByHash(md5('__TEST_VISITOR_3__'));
		$arBaskets[4] = Basket::getByHash(md5('__TEST_VISITOR_4__'));
		$arBaskets[0] = $arBaskets[0]->getFields('ID');
		$arBaskets[1] = $arBaskets[1]->getFields('ID');
		$arBaskets[2] = $arBaskets[2]->getFields('ID');
		$arBaskets[3] = $arBaskets[3]->getFields('ID');
		$arBaskets[4] = $arBaskets[4]->getFields('ID');
		$rsVisitorBasket = self::$_BasketItemDBS->getList(null, array(
			'BASKET_ID' => array(
				$arBaskets[0],
				$arBaskets[1],
				$arBaskets[2],
				$arBaskets[3],
				$arBaskets[4],
			),
			'!BASKET_ID' => array(
				$arBaskets[0],
				$arBaskets[1],
				$arBaskets[2],
				$arBaskets[3],
				$arBaskets[4],
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
SELECT I.ID AS ID, B.ORDER_ID AS ORDER_ID, I.BASKET_ID AS BASKET_ID, I.PRODUCT_ID AS PRODUCT_ID, I.PRODUCT_NAME AS PRODUCT_NAME, I.QUANTITY AS QUANTITY, I.WEIGHT AS WEIGHT, I.PRICE_ID AS PRICE_ID, I.PRICE_VALUE AS PRICE_VALUE FROM obx_basket_items AS I LEFT JOIN obx_basket AS B ON (B.ID = I.BASKET_ID) WHERE (1=1) AND ( I.BASKET_ID = '{$arBaskets[0]}' OR I.BASKET_ID = '{$arBaskets[1]}' OR I.BASKET_ID = '{$arBaskets[2]}' OR I.BASKET_ID = '{$arBaskets[3]}' OR I.BASKET_ID = '{$arBaskets[4]}' ) AND ( I.BASKET_ID <> '{$arBaskets[0]}' AND I.BASKET_ID <> '{$arBaskets[1]}' AND I.BASKET_ID <> '{$arBaskets[2]}' AND I.BASKET_ID <> '{$arBaskets[3]}' AND I.BASKET_ID <> '{$arBaskets[4]}' ) AND (B.ORDER_ID IS NULL) AND ( B.ORDER_ID IS NOT NULL AND B.ORDER_ID <> '1' AND B.ORDER_ID <> '' AND B.ORDER_ID <> '2' ) AND ((1<>1) OR (I.PRICE_VALUE > '10.32') OR (I.PRICE_VALUE < '1000.46') ) AND ((1<>1) OR (I.DISCOUNT_VALUE < '0.10') OR (I.DISCOUNT_VALUE >= '0.00') ) AND ((1<>1) OR (I.VAT_ID IS NOT NULL) OR (I.VAT_VALUE < '30.46') ) AND ((1<>1) OR (I.VAT_ID IS NOT NULL) OR (I.VAT_VALUE < '30.46') ) AND ((1<>1) OR (I.VAT_ID = '') OR (I.PRICE_VALUE = '100') ) ORDER BY I.ID ASC
SQL
			, $lastQueryString);
		foreach($arBaskets as $basketID) {
			self::$_BasketDBS->delete($basketID);
		}
	}
}
