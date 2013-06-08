<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\Currency;

require_once dirname(__FILE__).'/_Basket.php';

class OBX_Test_BasketList extends OBX_Test_Lib_Basket
{

	static protected $_arTestBasketCRUD = array();

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

	public function testCleanTestData() {
		self::$_BasketDBS->deleteByFilter(array(
			'ORDER_ID' => self::$_arTestOrder['ID']
		));
		self::$_BasketDBS->deleteByFilter(array(
			'USER_ID' => self::$_arTestUser['ID']
		));
		self::$_BasketDBS->deleteByFilter(array(
			'HASH_STRING' => md5('__TEST_BASKET_4_DELETION__')
		));
		self::$_BasketDBS->clearMessagePool();
	}

	public function testAddAnonBasket() {
		$newBasketID = self::$_BasketDBS->add(array(
			'HASH_STRING' => md5('__TEST_BASKET_4_DELETION__'),
			'CURRENCY' => Currency::getDefault()
		));
		if( $newBasketID < 1 ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		self::$_arTestBasketCRUD['BY_HASH'] = array('ID' => $newBasketID);
	}

	/**
	 * @depends testAddAnonBasket
	 */
	public function testTryAddBasketWithHashDuplicate() {
		$newBasketID = self::$_BasketDBS->add(array(
			'HASH_STRING' => md5('__TEST_BASKET_4_DELETION__'),
			'CURRENCY' => Currency::getDefault()
		));
		$this->assertLessThan(1, $newBasketID, 'Error: method add() passed duplicate basket HASH_STRING');
		$arError = self::$_BasketDBS->popLastError('ARRAY');
		if( $arError['CODE'] != 3 ) {
			$this->fail('Error: add() must return error code = 3, but returned code = '.$arError['CODE'].'; text: '.$arError['TEXT']);
		}
	}


	public function testAddBasket4User() {
		$newBasketID = self::$_BasketDBS->add(array(
			'USER_ID' => self::$_arTestUser['ID'],
			'CURRENCY' => Currency::getDefault()
		));
		if( $newBasketID < 1 ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		self::$_arTestBasketCRUD['4_USER'] = array('ID' => $newBasketID);
	}

	/**
	 * @depends testAddBasket4User
	 */
	public function testAddBasket4UserDuplicate() {
		$newBasketID = self::$_BasketDBS->add(array(
			'USER_ID' => self::$_arTestUser['ID'],
			'CURRENCY' => Currency::getDefault()
		));
		$this->assertLessThan(1, $newBasketID, 'Error: method add() passed duplicate basket ORDER_ID');
		$arError = self::$_BasketDBS->popLastError('ARRAY');
		if( $arError['CODE'] != 2 ) {
			$this->fail('Error: add() must return error code = 2, but returned code = '.$arError['CODE'].'; text: '.$arError['TEXT']);
		}
	}

	public function testAddBasket4Order() {
		$newBasketID = self::$_BasketDBS->add(array(
			'ORDER_ID' => self::$_arTestOrder['ID'],
			'CURRENCY' => Currency::getDefault()
		));
		if( $newBasketID < 1 ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		self::$_arTestBasketCRUD['4_ORDER'] = array('ID' => $newBasketID);
	}

	/**
	 * @depends testAddBasket4Order
	 */
	public function testAddBasket4OrderDuplicate() {
		$newBasketID = self::$_BasketDBS->add(array(
			'ORDER_ID' => self::$_arTestOrder['ID'],
			'CURRENCY' => Currency::getDefault()
		));
		$this->assertLessThan(1, $newBasketID, 'Error: method add() passed duplicate basket ORDER_ID');
		$arError = self::$_BasketDBS->popLastError('ARRAY');
		if( $arError['CODE'] != 1 ) {
			$this->fail('Error: add() must return error code = 1, but returned code = '.$arError['CODE'].'; text: '.$arError['TEXT']);
		}
	}

	/**
	 * @depends testAddAnonBasket
	 */
	public function testGetByHash() {
		$arBasket = self::$_BasketDBS->getListArray(null, array('HASH_STRING' => md5('__TEST_BASKET_4_DELETION__')));
		if( empty($arBasket) ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$this->assertEquals(1, count($arBasket), 'Error: getList() returned more then one basket with unique HASH_STRING');
		$arBasket = $arBasket[0];
		$this->assertArrayHasKey('ID', $arBasket);
		$this->assertArrayHasKey('USER_ID', $arBasket);
		$this->assertArrayHasKey('ORDER_ID', $arBasket);
		$this->assertEquals(self::$_arTestBasketCRUD['BY_HASH']['ID'], $arBasket['ID']);
		$this->assertEquals(md5('__TEST_BASKET_4_DELETION__'), $arBasket['HASH_STRING']);
		$this->assertEmpty($arBasket['USER_ID']);
		$this->assertEmpty($arBasket['ORDER_ID']);
		self::$_arTestBasketCRUD['BY_HASH'] = $arBasket;
	}

	/**
	 * @depends testAddBasket4User
	 */
	public function testGetByUser() {
		$arBasket = self::$_BasketDBS->getListArray(null, array('USER_ID' => self::$_arTestUser['ID']));
		if( empty($arBasket) ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$this->assertEquals(1, count($arBasket), 'Error: getList() returned more then one basket with unique USER_ID');
		$arBasket = $arBasket[0];
		$this->assertArrayHasKey('ID', $arBasket);
		$this->assertArrayHasKey('USER_ID', $arBasket);
		$this->assertArrayHasKey('ORDER_ID', $arBasket);
		$this->assertEquals(self::$_arTestBasketCRUD['4_USER']['ID'], $arBasket['ID']);
		$this->assertEquals(self::$_arTestUser['ID'], $arBasket['USER_ID']);
		$this->assertEmpty($arBasket['ORDER_ID']);
		self::$_arTestBasketCRUD['4_USER'] = $arBasket;
	}

	/**
	 * @depends testAddBasket4Order
	 */
	public function testGetByOrder() {
		$arBasket = self::$_BasketDBS->getListArray(null, array('ORDER_ID' => self::$_arTestOrder['ID']));
		if( empty($arBasket) ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$this->assertEquals(1, count($arBasket), 'Error: getList() returned more then one basket with unique ORDER_ID');
		$arBasket = $arBasket[0];
		$this->assertArrayHasKey('ID', $arBasket);
		$this->assertArrayHasKey('USER_ID', $arBasket);
		$this->assertArrayHasKey('ORDER_ID', $arBasket);
		$this->assertEquals(self::$_arTestBasketCRUD['4_ORDER']['ID'], $arBasket['ID']);
		$this->assertEquals(self::$_arTestOrder['ID'], $arBasket['ORDER_ID']);
		$this->assertEmpty($arBasket['USER_ID']);
		self::$_arTestBasketCRUD['4_ORDER'] = $arBasket;
	}


	/**
	 * Тест прикрепления простой корзины к корзине пользователя
	 * Этот тест должен обработать ошибку существования записи в БД
	 * USER_ID => self::$__testUserID,
	 * ORDER_ID => null
	 * @depends testAddAnonBasket
	 * @depends testGetByHash
	 * @depends testGetByUser
	 */
	public function testTryToMoveAnonBasket2UserBasket() {
		self::$_arTestBasketCRUD['BY_HASH']['USER_ID'] = self::$_arTestUser['ID'];
		$bSuccess = self::$_BasketDBS->update(self::$_arTestBasketCRUD['BY_HASH']);
		$this->assertFalse($bSuccess, 'Error: update() must return false in this case');
		$arError = self::$_BasketDBS->popLastError('ARRAY');
		// code 6: Невозможно прикрепить Анонимную Корзину я к пользователю. У пользователя уже есть корзина
		if( $arError['CODE'] != 6) {
			$this->fail('Error: update() must return error code = 6, but returned code = '.$arError['CODE'].'; text: '.$arError['TEXT']);
		}
		$this->assertNotEmpty($arError, 'Error: update() not returned text of the error');
	}

	/**
	 * Тест прикрекления Корзины Пользователя к Заказу
	 * Этот тест должен обработать ошибку существования записи в БД
	 * USER_ID => self::$__testUserID,
	 * ORDER_ID => self::$__arTestOrder['ID']
	 * @depends testAddBasket4User
	 * @depends testGetByUser
	 * @depends testGetByOrder
	 */
	public function testTryToMoveUserBasket2OrderBasket() {
		self::$_arTestBasketCRUD['4_USER']['ORDER_ID'] = self::$_arTestOrder['ID'];
		$bSuccess = self::$_BasketDBS->update(self::$_arTestBasketCRUD['4_USER']);
		$this->assertFalse($bSuccess, 'Error: update() must return false in this case');
		$arError = self::$_BasketDBS->popLastError('ARRAY');
		if( $arError['CODE'] != 4) {
			$this->fail('Error: update() must return error code = 4, but returned code = '.$arError['CODE'].'; text: '.$arError['TEXT']);
		}
		$this->assertNotEmpty($arError, 'Error: update() not returned text of the error');
	}

	/**
	 * Удаляем корзину заказа
	 * @depends testAddBasket4Order
	 */
	public function testDeleteOrderBasket() {
		$bSuccess = self::$_BasketDBS->delete(self::$_arTestBasketCRUD['4_ORDER']['ID']);
		if( !$bSuccess ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
	}

	/**
	 * Пытаемся прикрепить корзину пользователю к заказу другого опльзователя
	 * Тест должен обработать ошибку с кодом 8
	 */
	public function testTryToMoveUserBasket2AlienOrder() {
		self::$_arTestBasketCRUD['4_USER']['ORDER_ID'] = self::$_arSomeOthTestOrder['ID'];
		$bSuccess = self::$_BasketDBS->update(self::$_arTestBasketCRUD['4_USER']);
		$this->assertFalse($bSuccess, 'Error: Basket moved to alien order');
		$arError = self::$_BasketDBS->popLastError('ARRAY');
		if( $arError['CODE'] != 8) {
			$this->fail('Error: update() must return error code = 8, but returned code = '.$arError['CODE'].'; text: '.$arError['TEXT']);
		}
		$this->assertNotEmpty($arError, 'Error: update() not returned text of the error');
	}

	/**
	 * Снова перемещаем корзину пользователя к заказу
	 * Этот тест должен отработать, поскульку теперь в БД нет конфилктующей записи
	 */
	public function testMoveUserBasket2OrderBasket() {
		self::$_arTestBasketCRUD['4_USER']['ORDER_ID'] = self::$_arTestOrder['ID'];
		$bSuccess = self::$_BasketDBS->update(self::$_arTestBasketCRUD['4_USER']);
		if( !$bSuccess ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arUserToOrderBasket = self::$_BasketDBS->getByID(self::$_arTestBasketCRUD['4_USER']['ID']);
		$this->assertArrayHasKey('HASH_STRING', $arUserToOrderBasket);
		$this->assertArrayHasKey('USER_ID', $arUserToOrderBasket);
		$this->assertArrayHasKey('ORDER_ID', $arUserToOrderBasket);
		$this->assertEquals(self::$_arTestOrder['ID'], $arUserToOrderBasket['ORDER_ID']);
		$this->assertNull($arUserToOrderBasket['HASH_STRING']);
		$this->assertNull($arUserToOrderBasket['USER_ID']);
	}

	/**
	 * Так как корзина пользователя стала корзиной заказа,
	 * теперь можно переметстить и корзину по хешу
	 */
	public function testMoveHashBasket2UserBasket() {
		self::$_arTestBasketCRUD['BY_HASH']['USER_ID'] = self::$_arTestUser['ID'];
		$bSuccess = self::$_BasketDBS->update(self::$_arTestBasketCRUD['BY_HASH']);
		if( !$bSuccess ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
		$arAnonToUserBasket = self::$_BasketDBS->getByID(self::$_arTestBasketCRUD['BY_HASH']['ID']);
		$this->assertArrayHasKey('HASH_STRING', $arAnonToUserBasket);
		$this->assertArrayHasKey('USER_ID', $arAnonToUserBasket);
		$this->assertArrayHasKey('ORDER_ID', $arAnonToUserBasket);
		$this->assertEquals(self::$_arTestUser['ID'], $arAnonToUserBasket['USER_ID']);
		$this->assertNull($arAnonToUserBasket['HASH_STRING']);
		$this->assertNull($arAnonToUserBasket['ORDER_ID']);
	}

	/**
	 * Удаляем корзину пользователя (которая теперь уже корзина заказа)
	 * @depends testAddBasket4User
	 */
	public function testDeleteUserBasket() {
		$bSuccess = self::$_BasketDBS->delete(self::$_arTestBasketCRUD['4_USER']['ID']);
		if( !$bSuccess ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
	}

	/**
	 * Удаляем корзину по хешу, которая теперь принадлежит пользователю
	 * @depends testAddAnonBasket
	 */
	public function testDeleteBasketByHash() {
		$bSuccess = self::$_BasketDBS->delete(self::$_arTestBasketCRUD['BY_HASH']['ID']);
		if( !$bSuccess ) {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			$this->fail('Error: '.$arError['TEXT'].'; code: '.$arError['CODE']);
		}
	}
}