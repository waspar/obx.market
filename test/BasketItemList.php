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

	public function testAddTestOrder() {

	}

	/**
	 * Добавления товара в корзину
	 */
	public function testAdd4Visitor() {

	}

	/**
	 * Добавление товарв к заказу
	 */
	public function testAddOrder() {

	}

	/**
	 * Этот тест должег обработать ошибку на принадлежность инфоблока к Торговым каталогам
	 */
	public function testAddNotAProduct() {

	}

	public function testGetListFromVisitor() {

	}

	public function testGetListFromOrder() {

	}

	public function testUpdate() {

	}

	/**
	 * Этот тест должен обработать ошибку, поскуольку нельщя обновить саму позицию.
	 * Если необходимо сменить товарв в корзине, то необходимо старый товар удалить и новый добавить
	 */
	public function testTryToUpdateProductID() {

	}

	public function testDeleteFromVisitor() {

	}

	public function testDeleteFromOrder() {

	}

	public function testSimpleDeletion() {

	}
}