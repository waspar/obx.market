<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

IncludeModuleLangFile(__FILE__);

class Basket extends \OBX_CMessagePoolDecorator
{
	const COOKIE_NAME = 'OBX_BASKET_HASH';

	/**
	 * @var bool
	 * @static
	 * @access protected
	 */
	static protected $_bDBSimpleObjectInitialized = false;

	/**
	 * @var BasketDBS
	 * @static
	 * @access protected
	 */
	static protected $_BasketDBS = null;

	/**
	 * @var BasketItemDBS
	 * @static
	 * @access protected
	 */
	static protected $_BasketItemDBS = null;

	/**
	 * @var OrderDBS
	 * @static
	 * @access protected
	 */
	static protected $_OrderDBS = null;

	/**
	 * @var PriceDBS
	 * @static
	 * @access protected
	 */
	static protected $_PriceDBS = null;

	/**
	 * @var null | \OBX_DBSResult
	 */
	protected $_rsBasket = null;
	protected $_arFields = array(
		'ID' => null
	);
	protected $_arProductList = array();
	protected $_arItemsList = array();

	static protected function _initDBSimpleObjects() {
		self::$_BasketDBS = BasketDBS::getInstance();
		self::$_BasketItemDBS = BasketItemDBS::getInstance();
		self::$_OrderDBS = OrderDBS::getInstance();
		self::$_PriceDBS = PriceDBS::getInstance();
	}
	static public function getByID($basketID) {
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
		$rsBasket = self::$_BasketDBS->getByID($basketID, null, true);
		return new self($rsBasket);
	}
	static public function getByHash($hash) {
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
		$hash = substr($hash, 0, 32);
		$rsBasket = self::$_BasketDBS->getList(null, array(
			'HASH_STRING' => $hash,
			'ORDER_ID' => null
		));
		if( $rsBasket->SelectedRowsCount() < 1 ) {
			$newBasketID = self::$_BasketDBS->add(array('HASH_STRING'	=> $hash));
			$rsBasket = self::$_BasketDBS->getByID($newBasketID, null, true);
		}
		return new self($rsBasket);
	}
	static public function getByUserID($userID) {
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
		$rsBasket = self::$_BasketDBS->getList(null, array(
			'USER_ID' => $userID,
			'ORDER_ID' => null
		));
		if( $rsBasket->SelectedRowsCount() < 1 ) {
			$newBasketID = self::$_BasketDBS->add(array('USER_ID'	=> $userID));
			$rsBasket = self::$_BasketDBS->getByID($newBasketID, null, true);
		}
		return new self($rsBasket);
	}
	static public function getByOrderID($orderID) {
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
		$rsBasket = self::$_BasketDBS->getList(null, array(
			'ORDER_ID' => $orderID
		));
		if( $rsBasket->SelectedRowsCount() < 1 ) {
			$newBasketID = self::$_BasketDBS->add(array('ORDER_ID'	=> $orderID));
			$rsBasket = self::$_BasketDBS->getByID($newBasketID, null, true);
		}
		return new self($rsBasket);
	}

	/**
	 * @return null | self
	 */
	static public function getCurrent() {
		global $USER, $APPLICATION;
		$BasketByUser = null;
		if( $USER->IsAuthorized() ) {
			$BasketByUser = self::getByUserID($USER->GetID());
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
			$_COOKIE[\COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_".self::COOKIE_NAME] = $currenctCookieID;
			// ^^^ cookie hack
			$APPLICATION->set_cookie(Basket::COOKIE_NAME, $currenctCookieID);

			$BasketByUser = self::getByHash($currenctCookieID);
		}
		return $BasketByUser;
	}

	public function __construct(\OBX_DBSResult $rsBasket) {
		if($rsBasket != null && $rsBasket->SelectedRowsCount() > 0) {
			$abstractionName = get_class(self::$_BasketDBS);
			if($rsBasket->getAbstractionName() != $abstractionName) {
				$this->addError('Error: Basket must be constructed from the result of '.$abstractionName.'::getList()');
			}
			$this->_arFields = $rsBasket->Fetch();
		}
		else {
			$arError = self::$_BasketDBS->popLastError('ARRAY');
			if(strlen($arError['TEXT']) > 0) {
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

	public function syncProductList() {
		$userID = intval($this->_arFields['USER_ID']);
		$orderID = intval($this->_arFields['ORDER_ID']);
		$arSelect = array(
			'ID',
			'BASKET_ID',
			//'ORDER_ID',
			//'USER_ID',
			'PRODUCT_ID',
			'PRODUCT_NAME',
			'QUANTITY',
			'WEIGHT',
			'PRICE_ID',
			//'PRICE_CODE',
			//'PRICE_NAME',
			'PRICE_VALUE',
			'DISCOUNT_VALUE',
			//'VAT_ID',
			//'VAT_VALUE',
			'IB_ELT_ID',
			'IB_ELT_NAME',
			'IB_ELT_CODE',
			'IB_ELT_SECTION_ID',
			'IB_ELT_SECTION_CODE',
			'IB_ELT_SORT',
			'IB_ELT_PREVIEW_TEXT',
			'IB_ELT_PREVIEW_PICTURE',
			'IB_ELT_DETAIL_TEXT',
			'IB_ELT_DETAIL_PICTURE',
			'IB_ELT_XML_ID',
			'IB_ELT_TIMESTAMP_X',
			'IB_ELT_MODIFIED_BY',
			'IB_ELT_LIST_PAGE_URL',
			'IB_ELT_SECTION_PAGE_URL',
			'IB_ELT_DETAIL_PAGE_URL',
			//'IB_ELT_SITE_ID',
			//'IB_ELT_SITE_DIR',
		);
		if( $userID > 0 ) {
			$arBasketItems = $this->_BasketItemDBS->getListArray(null, array('USER_ID' => $userID), null, null, $arSelect);
		}
		elseif( $orderID > 0 ) {
			$arBasketItems = $this->_BasketItemDBS->getListArray(null, array('ORDER_ID' => $orderID), null, null, $arSelect);
		}
		elseif( strlen($this->_arFields['HASH_STRING']) == 32 ) {
			$arBasketItems = $this->_BasketItemDBS->getListArray(null, array('HASH_STRING' => $this->_arFields['HASH_STRING']));
		}
		else {
			$this->addError(GetMessage('OBX_BASKET_ERROR_1'));
			return false;
		}
		if( count($arBasketItems)>1 ) {
			$this->_arProductList = $arBasketItems;
		}
		return true;
	}

	public function addProduct($productID, $quantity = 1, $priceValue = null, $priceID = null) {
		$quantity = intval($quantity);
		$quantity = ($quantity<1)?1:$quantity;
		$productID = intval($productID);
		if($productID<1) {
			return -1;
		}
		if( $priceID !== null ) {
			$arAvailPriceList = self::$_PriceDBS->getAvailPriceForUser($this->_arFields['USER_ID']);
			if( ! in_array($priceID, $arAvailPriceList) ) {
				$this->addWarning('OBX_BASKET_WARNING_1', 1);
			}
		}
		//$arOptimalPrice = Price::getOptimalProductPrice($productID, $this->_arFields['USER_ID']);
		//if()
		if( array_key_exists($productID, $this->_arItemsList) ) {
			$bSuccess = self::$_BasketItemDBS->update(array(
				'BASKET_ID' => $this->_arFields['ID'],
				'PRODUCT_ID' => $productID,
				'QUANTITY' => $this->_arItemsList[$productID]
			));
			if(!$bSuccess) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				$this->addError(GetMessage('OBX_BASKET_ERROR_200').' '.$arError['TEXT'], (200 + $arError['CODE']));
				return -1;
			}
			$this->_arItemsList[$productID] = $this->_arItemsList[$productID] + $quantity;
		}
		else {
			$newID = self::$_BasketItemDBS->add(array(
				'BASKET_ID' => $this->_arFields['ID'],
				'PRODUCT_ID' => $productID,
				'QUANTITY' => $quantity
			));
			if($newID < 1) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				$this->addError(GetMessage('OBX_BASKET_ERROR_300').' '.$arError['TEXT'], (300 + $arError['CODE']));
				return -1;
			}
			$this->_arItemsList[$productID] = $quantity;
		}
		return $this->_arItemsList[$productID];
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

	public function removeProduct() {

	}

	public function getCost() {

	}

	public function clear() {

	}

	public function mergeBasket(self $Basket, $bClearMergedBasket = false) {

	}
}
