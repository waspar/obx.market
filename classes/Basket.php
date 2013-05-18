<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

class OBX_Basket extends OBX_CMessagePoolDecorator
{
	const COOKIE_NAME = 'OBX_BASKET_HASH';

	/**
	 * @var bool
	 * @static
	 * @access protected
	 */
	static protected $_bDBSimpleObjectInitialized = false;

	/**
	 * @var OBX_BasketDBS
	 * @static
	 * @access protected
	 */
	static protected $_BasketDBS = null;

	/**
	 * @var OBX_BasketItemDBS
	 * @static
	 * @access protected
	 */
	static protected $_BasketItemDBS = null;

	/**
	 * @var OBX_OrderDBS
	 * @static
	 * @access protected
	 */
	static protected $_OrderDBS = null;

	/**
	 * @var OBX_PriceDBS
	 * @static
	 * @access protected
	 */
	static protected $_PriceDBS = null;

	protected $_arFields = array(
		'ID' => null
	);
	protected $_arProductList = array();

	static protected function _initDBSimpleObjects() {
		self::$_BasketDBS = OBX_BasketDBS::getInstance();
		self::$_BasketItemDBS = OBX_BasketItemDBS::getInstance();
		self::$_OrderDBS = OBX_OrderDBS::getInstance();
		self::$_PriceDBS = OBX_PriceDBS::getInstance();
	}
	static public function getByID($basketID) {
		return new self(intval($basketID), null, null, null);
	}
	static public function getByHash($hash) {
		return new self(null, substr($hash, 0, 32), null, null);
	}
	static public function getByUserID($userID) {
		return new self(null, null, intval($userID), null);
	}
	static public function getByOrderID($orderID) {
		return new self(null, null, null, intval($orderID));
	}

	/**
	 * @return null | self
	 */
	static public function getCurrent() {
		global $USER, $APPLICATION;
		$BasketByUser = null;
		if( $USER->IsAuthorized() ) {
			$BasketByUser = new self(null, null, $USER);
		}
		else {
			$currenctCookieID = trim($APPLICATION->get_cookie(self::COOKIE_NAME));
			if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
			if( ! self::$_BasketDBS->__check_HASH_STRING($currenctCookieID) ) {
				$currenctCookieID = self::generateHash();
			}
			// Данный код нужен для выполнения автоматического тестирования в cli-режиме
			// +++ cookie hack [pr0n1x:2013-05-01]
			// простой hack для эмулирвоания наличия кукисов.
			// Если задать хотя бы одно значение в $_COOKIE в cli-режиме,
			// массив так и сотается суперглобальным
			// $APPLICATION->set_cookie() не поможет ибо использует встроенную ф-ию php setcookie(),
			// а та в свою очередь не модифицирует $_COOKIE, а просто формирует header http-ответа
			// потому нужно вручную модифицировать $_COOKIE в cli-режиме, что бы отработала ф-ия $APPLICATION->get_cookie()
			$_COOKIE[COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_".OBX_Basket::COOKIE_NAME] = $currenctCookieID;
			// ^^^ cookie hack
			$APPLICATION->set_cookie(OBX_Basket::COOKIE_NAME, $currenctCookieID);
			$BasketByUser = new self(null, $currenctCookieID, null, null);
		}
		return $BasketByUser;
	}

	protected function __construct($basketID = null, $basketHash = null, $userID = null, $orderID = null) {
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();

		$rsBasket = null;
		if($basketID !== null) {
			$rsBasket = self::$_BasketDBS->getByID($basketID, null, true);
		}
		elseif($basketHash !== null) {
			$rsBasket = self::$_BasketDBS->getList(null, array(
				'HASH_STRING' => $basketHash,
				'ORDER_ID' => null
			));
		}
		elseif($userID !== null) {
			$rsBasket = self::$_BasketDBS->getList(null, array(
				'USER_ID' => $userID,
				'ORDER_ID' => null
			));
		}
		elseif($orderID !== null) {
			$rsBasket = self::$_BasketDBS->getList(null, array(
				'ORDER_ID' => $orderID
			));
		}

		if($rsBasket != null && $arBasket = $rsBasket->Fetch()) {
			$this->_arFields = $arBasket;
		}
		else {
			$newBasketID = self::$_BasketDBS->add(array(
				'USER_ID'	=> $userID,
				'ORDER_ID'	=> $orderID,
				'HASH_STRING'		=> $basketHash
			));
			if( $newBasketID > 0 ) {
				$this->_arFields = self::$_BasketDBS->getByID($newBasketID);
			}
			else {
				$arError = self::$_BasketDBS->popLastError('ARRAY');
				$this->addError($arError['TEXT'], $arError['CODE']);
			}
		}
	}
	final protected function __clone() {}

	static public function generateHash() {
		if( !array_key_exists('REMOTE_ADDR', $_SERVER) ) {
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		}
		if( !array_key_exists('HTTP_USER_AGENT', $_SERVER) ) {
			$_SERVER['HTTP_USER_AGENT'] = 'local test user agent';
		}
		return md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].microtime().mt_rand());
	}

	public function mergeBasket(self $Basket) {

	}

	public function syncProductList() {
		//$this->_BasketItemDBS->getListArray();
		//$this->_arProductList
	}

	public function getFields($fieldName = null) {
		if($fieldName !== null) {
			if(array_key_exists($fieldName, $this->_arFields)) {
				return $this->_arFields[$fieldName];
			}
			else {
				return null;
			}
		}
		return $this->_arFields;
	}

	/**
	 * Получить число позиций номенклатуры
	 * @return int
	 */
	public function getProductCount() {
		return 0;
	}

	/**
	 * Получить общее число товаров (с учетом количесва каждой позиции номенклатуры)
	 * @return int
	 */
	public function getItemsCount() {
		return 0;
	}

	/**
	 * Получить список продуктов
	 */
	public function getProductList() {

	}

	public function addProduct($productID, $quantity = 1) {

	}

	public function removeProduct() {

	}

	public function getCost() {

	}

	public function clear() {

	}
}
