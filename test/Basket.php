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

/**
 * Тест близок по структуре к OBX_BasketItemList
 * с тем лишь отличием, что тут мы тестируем обертку над OBX\BasketItem ,
 * которая автоматизирует работу с Visitors
 * Class OBX_Test_BasketItem
 */
final class OBX_Test_BasketItem extends OBX_Market_TestCase
{
	static private $_cookieID = null;
	static private $_arPoductList = array();
	static private $_arTestIBlock = array();

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
	}

	public function testGetTestIBlockData() {
		$rsTestIB = CIBlock::GetList(array('SORT' => 'ASC'), array('CODE' => self::OBX_TEST_IB_1));
		if( $arTestIB = $rsTestIB->GetNext() ) {
			self::$_arTestIBlock = $arTestIB;
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

	public function testAddTestVisitor() {
		$Visitor = new OBX_Visitor(array('COOKIE_ID' => self::$_cookieID));
		if( $Visitor->getFields('ID') == null) {
			$this->fail('Error: '.$Visitor->popLastError());
		}
	}

	public function testAddItems2Basket() {
		OBX_Basket::getInstance();
		$Basket = OBX_Basket::getInstance(new OBX_Visitor(array('COOKIE_ID' => self::$_cookieID)));
		print_r($Basket);
	}

	public function testAuthorizeUser() {
		global $USER;
		$USER->Authorize(1);
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
}
