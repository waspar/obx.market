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
	static public function getCurrent() {
		global $USER, $APPLICATION;
		$BasketByUser = null;
		if( $USER->IsAuthorized() ) {
			$BasketByUser = new self(null, null, $USER);
		}
		$currenctCookieID = trim($APPLICATION->get_cookie(self::COOKIE_NAME));
		if( self::$_BasketDBS->__check_HASH_STRING($currenctCookieID) ) {

		}
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

	public function generateHash() {

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
