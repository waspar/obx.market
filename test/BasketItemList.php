<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

class OBX_BasketItemList extends OBX_Market_TestCase
{
	/**
	 * @var string
	 * @static
	 * @access private
	 */
	static private $_cookieID = null;

	/**
	 * @var array
	 * @static
	 * @access private
	 */
	static private $_arPoductList = array();

	/**
	 * @var array
	 * @static
	 * @access private
	 */
	static private $_arTestIBlock = array();

	/**
	 * @var OBX_BasketItemDBS
	 * @static
	 * @access private
	 */
	static private $_BasketItemDBS = null;

	/**
	 * @var OBX_Visitor
	 * @static
	 * @access private
	 */
	static private $_Visitor = null;

	/**
	 * @var OBX_PriceDBS
	 * @static
	 * @access private
	 */
	static private $_PriceDBS = null;

	/**
	 * @var array
	 * @static
	 * @access private
	 */
	static private $_arPrice = 0;

	/**
	 * @var OBX_OrderDBS
	 * @static
	 * @access private
	 */
	static private $_OrderDBS = null;

	/**
	 * @var array
	 * @static
	 * @access private
	 */
	static private $_arTestOrder = array();

	static public function setUpBeforeClass() {
		global $USER, $_COOKIE;
		$USER->Logout();
		self::$_cookieID = md5('__TEST_COOKIE_ID__');
		// +++ cookie hack [pr0n1x:2013-05-01]
		// простой hack для эмулирвоания наличия кукисов.
		// Если задать хотя бы одно значение в $_COOKIE в cli-режиме,
		// массив так и сотается суперглобальным
		// $APPLICATION->set_cookie() не поможет ибо использует втроенную ф-ию php setcookie(),
		// а та в свою очередь не модифицирует $_COOKIE, а просто формирует header http-ответа
		// потому нужно вручную модифицировать $_COOKIE в cli-режиме, что бы отработала ф-ия $APPLICATION->get_cookie()
		$_COOKIE[COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_".OBX_Visitor::VISITOR_COOKIE_NAME] = self::$_cookieID;
		// ^^^ cookie hack
		self::$_BasketItemDBS = OBX_BasketItemDBS::getInstance();
		self::$_PriceDBS = OBX_PriceDBS::getInstance();
		self::$_OrderDBS = OBX_OrderDBS::getInstance();
	}

	public function testGetTestVisitor() {
		self::$_Visitor = new OBX_Visitor(array('COOKIE_ID' => self::$_cookieID));
		if( self::$_Visitor->getFields('ID') == null) {
			$this->fail('Error: '.self::$_Visitor->popLastError());
		}
	}

	public function testAddTestPrice() {
		$newPriceID = self::$_PriceDBS->add(array(
			'CURRENCY' => 'RUB',
			'NAME' => '__TEST_PRICE',
			'CODE' => '__TEST_PRICE'
		));
		if($newPriceID<1) {
			$arError = self::$_PriceDBS->popLastError('ARRAY');
			// error code = 3 - уже существует
			if($arError['CODE'] != 3) {
				$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
			}
		}
		$arPriceList = self::$_PriceDBS->getListArray(null, array('CODE' => '__TEST_PRICE'));
		if( empty($arPriceList) ) {
			$this->fail('test price not found.');
		}
		self::$_arPrice = $arPriceList[0];

		// Делаем цену доступной для всех пользователей
		self::$_PriceDBS->addGroup(self::$_arPrice['ID'], 2);
	}

	/**
	 * @depends testAddTestPrice
	 */
	public function testGetTestIBlockData() {
		$rsTestIB = CIBlock::GetList(array('SORT' => 'ASC'), array('CODE' => self::OBX_TEST_IB_1));
		if( $arTestIB = $rsTestIB->GetNext() ) {

			// Делаем инфоблок торговым
			OBX_ECommerceIBlockDBS::getInstance()->add(array(
				'IBLOCK_ID' => $arTestIB['ID']
			));
			// Проверяем что инфоблок стал торговым
			$arTestEComIB = OBX_ECommerceIBlock::getByID($arTestIB['ID']);
			if( empty($arTestEComIB) ) {
				$this->fail('test iblock isn\'t an e-commerce catalog');
			}
			self::$_arTestIBlock = $arTestIB;

			// Получаем идентификор свойства являющегося содержащего цену товаров
			$rsPricePropList = CIBlockProperty::GetList(array('ID' => 'ASC'), array('IBLOCK_ID' => $arTestIB['ID'], 'CODE' => 'PRICE'));
			if( !($arPriceIBProp = $rsPricePropList->GetNext()) ) {
				$this->fail('test iblock price property not found');
			}

			// Удаляем все привязки свойств инфоблока к ценам
			OBX_CIBlockPropertyPrice::deleteByFilter(array('IBLOCK_ID' => $arTestIB['ID']));
			// Добавляем привязку свойства инфоблока к тестовой цене
			$bSuccess = OBX_CIBlockPropertyPrice::add(array(
				'IBLOCK_ID' => $arTestIB['ID'],
				'IBLOCK_PROP_ID' => $arPriceIBProp['ID'],
				'PRICE_ID' => self::$_arPrice['ID']
			));
			if(!$bSuccess) {
				$this->fail('adding price iblock property link failed');
			}
			$rsProducts = CIBlockElement::GetList(array('SORT' => 'ASC'), array(
				'IBLOCK_ID' => self::$_arTestIBlock['ID']
			), false, array('nTopCount' => '20'));
			while($arProduct = $rsProducts->GetNext()) {
				self::$_arPoductList[] = $arProduct;
			}
			$this->assertGreaterThan(0, count(self::$_arPoductList));
		}
		else {
			$this->fail('test iblock not found');
		}
	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 */
	public function testAddTestOrder() {
		$newOrderID = self::$_OrderDBS->add();
		if($newOrderID<1) {
			$arError = self::$_OrderDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arOrder = self::$_OrderDBS->getByID($newOrderID);
		$this->assertNotEmpty($arOrder, 'test order not found.');
		self::$_arTestOrder = $arOrder;
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
	 * Этот тест должег обработать ошибку на принадлежность инфоблока к Торговым каталогам
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testAddNotAProduct() {

	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testGetListFromVisitor() {

	}

	/**
	 * @depends testGetTestVisitor
	 * @depends testAddTestPrice
	 * @depends testGetTestIBlockData
	 * @depends testAddTestOrder
	 */
	public function testGetListFromOrder() {

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