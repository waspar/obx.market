<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

use OBX\Core\Tools;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\CMessagePoolDecorator;
use OBX\Core\DBSimple;
use OBX\Core\DBSResult;

IncludeModuleLangFile(__FILE__);

/**
 * Class Basket
 * @package OBX\Market
 *
 * TODO: Написать методы взаимодействия с валютами
 * setCurrency
 * getCurrency
 * Методы будут добавлять основную валюту корзины.
 * в конструкторе будем брать дефолтную Currency::getDefault()
 * если попытаться добавить товар с валютой отличной от валюты корзины, сгененрировать ошибку.
 * В дальнейшем, когда будет механизм курса валют, просто делать пересчет по курсу.
 */

class Basket extends CMessagePoolDecorator
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
	 * @var CurrencyFormatDBS
	 * @static
	 * @access protected
	 */
	static protected $_CurrencyFormatDBS = null;

	/**
	 * @var null | DBSResult
	 */
	protected $_rsBasket = null;
	protected $_arFields = array(
		'ID' => null
	);

	/**
	 * Содержит массив элементов, полученных из корзины
	 * в качестве ключей используется ID
	 * @var array
	 */
	protected $_arProductList = array();

	/**
	 * Массив ссылок на элементы $_arProductList
	 * в качестве ключей используется PRODUCT_ID
	 * @var array
	 */
	protected $_arProductListIndex = array();

	/**
	 * Вводим механизм ручного подсчета, поскольку:
	 * почему-то при попытку взять количество в этом массиве (return count($this->_arProductList);) интерпретатор версии
	 * PHP 5.3.10-1ubuntu3.6 with Suhosin-Patch (cli) (built: Mar 11 2013 14:31:48)
	 * падает в segfault во время отладки. А во время выолнения тестов без отладчика вохвращает на 1 больше
	 * проявляется на нескольких машинах с ubuntu-12.04
	 * ^^^
	 * @var int
	 */
	protected $_countProducts = 0;



	/**
	 * Массив содержит количества элементов корзины
	 * в качестве ключей используется PRODUCT_ID
	 * @var array
	 */
	protected $_arItemsQuantity = array();

	static protected function _initDBSimpleObjects() {
		// DevTop: [pronix:2013-06-14] первый нашедший данную строку в коде получит лицензию на dvt.marketpizza бесплатно

		self::$_BasketDBS = BasketDBS::getInstance();
		self::$_BasketItemDBS = BasketItemDBS::getInstance();
		self::$_OrderDBS = OrderDBS::getInstance();
		self::$_PriceDBS = PriceDBS::getInstance();
		self::$_CurrencyFormatDBS = CurrencyFormatDBS::getInstance();
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
			$newBasketID = self::$_BasketDBS->add(array(
				'HASH_STRING' => $hash,
				'CURRENCY' => Currency::getDefault()
			));
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
			$newBasketID = self::$_BasketDBS->add(array(
				'USER_ID' => $userID,
				'CURRENCY' => Currency::getDefault()
			));
			$rsBasket = self::$_BasketDBS->getByID($newBasketID, null, true);
		}
		return new self($rsBasket);
	}
	static public function getByOrderID($orderID) {
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
		$rsBasket = self::$_BasketDBS->getList(null, array('ORDER_ID' => $orderID));
		if( $rsBasket->SelectedRowsCount() < 1 ) {
			$newBasketID = self::$_BasketDBS->add(array(
				'ORDER_ID'	=> $orderID,
				'CURRENCY' => Currency::getDefault()
			));
			$rsBasket = self::$_BasketDBS->getByID($newBasketID, null, true);
		}
		return new self($rsBasket);
	}

	/**
	 * @return null | self
	 */
	static public function getCurrent() {
		global $USER;
		$BasketByUser = null;
		if( $USER->IsAuthorized() ) {
			$BasketByUser = self::getByUserID($USER->GetID());
		}
		else {
			$currentCookieID = self::getCurrentHash();
			$BasketByUser = self::getByHash($currentCookieID);
		}
		return $BasketByUser;
	}

	static public function getCurrentHash(){
		global $APPLICATION;
		$currentCookieID = trim($APPLICATION->get_cookie(self::COOKIE_NAME));
		if( ! self::$_bDBSimpleObjectInitialized ) self::_initDBSimpleObjects();
		if( ! self::$_BasketDBS->__check_HASH_STRING($currentCookieID) ) {
			$currentCookieID = self::generateHash();
		}
		// Данный код нужен для выполнения автоматического тестирования в cli-режиме
		// +++ cookie hack [pr0n1x:2013-05-01]
		// простой hack для эмулирвоания наличия кукисов.
		// Если задать хотя бы одно значение в $_COOKIE в cli-режиме,
		// массив так и сотается суперглобальным
		// $APPLICATION->set_cookie() не поможет ибо использует встроенную ф-ию php setcookie(),
		// а та в свою очередь не модифицирует $_COOKIE, а просто формирует header http-ответа
		// потому нужно вручную модифицировать $_COOKIE в cli-режиме, что бы отработала ф-ия $APPLICATION->get_cookie()
		$_COOKIE[\COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_".self::COOKIE_NAME] = $currentCookieID;
		// ^^^ cookie hack
		$APPLICATION->set_cookie(Basket::COOKIE_NAME, $currentCookieID);
		return $currentCookieID;
	}

	/**
	 * @param DBSResult $rsBasket
	 */
	public function __construct(DBSResult $rsBasket) {
		if($rsBasket != null && $rsBasket->SelectedRowsCount() > 0) {
			$abstractionName = get_class(self::$_BasketDBS);
			if($rsBasket->getAbstractionName() != $abstractionName) {
				$this->addError('Error: Basket must be constructed from the result of '.$abstractionName.'::getList()');
				return;
			}
			$this->_arFields = $rsBasket->Fetch();
			$this->syncProductList();
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
		return md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'].microtime().mt_rand().SITE_ID);
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
	 * @deprecated
	 * @return int
	 */
	public function getBasketID(){
		return $this->_arFields['ID'];
	}

	/**
	 * Получить общую стоимость корзины
	 */
	public function getCost($bFormat = false) {
		$cost = 0;
		foreach($this->_arProductList as &$arItem) {
			$cost += (floatVal($arItem['PRICE_VALUE']) * floatVal($arItem['QUANTITY']));
		}
		if($bFormat) {
			return self::$_CurrencyFormatDBS->formatPrice($cost, $this->_arFields['CURRENCY']);
		}
		return $cost;
	}

	/**
	 * @deprecated
	 */
	public function getBasketCost($bFormat = false) {
		return $this->getCost($bFormat);
	}

	/**
	 * Проверить корзину или наличие товара
	 * @param null $productID - проверить наличие конкретного продукта
	 * @return bool
	 */
	public function isEmpty($productID = null){
		if($productID !== null) {
			return (
				! array_key_exists($productID, $this->_arItemsQuantity)
				||
				$this->_arItemsQuantity[$productID]<1
			);
		}
		else {
			return (count($this->_arItemsQuantity)<1);
		}
	}

	/**
	 * Очистить корзину
	 */
	public function clear() {
		$bResultSuccess = true;
		$failCount = 0;
		foreach($this->_arItemsQuantity as $productID => $quantity) {
			$bSuccess = $this->removeProduct($productID);
			if(!$bSuccess) $failCount++;
			$bResultSuccess = $bResultSuccess && $bSuccess;
		}
		return $bResultSuccess;
	}
	/**
	 * @deprecated
	 */
	public function clearBasket() {
		return $this->clear();
	}


	/**
	 * Получить список продуктов
	 * @param bool $bReturnIndexedByProductID
	 * @return array
	 */
	public function getProductsList($bReturnIndexedByProductID = false) {
		if($bReturnIndexedByProductID) {
			$arIndexedByProductID = array();
			foreach($this->_arProductList as &$arItem) {
				$arIndexedByProductID[$arItem['PRODUCT_ID']] = $arItem;
			}
			return $arIndexedByProductID;
		}
		return $this->_arProductList;
	}

	/**
	 * Получить стоимость определенного товара
	 * @param $productID
	 * @param bool $bFormat
	 * @return float | null
	 */
	public function getProductCost($productID, $bFormat = false){
		if( array_key_exists($productID, $this->_arProductListIndex) ) {
			return ($this->_arProductListIndex[$productID]['PRICE_VALUE'] * $this->_arItemsQuantity[$productID]);
		}
		return null;
	}



	/**
	 * Получить цену продукта
	 * @param $productID
	 * @return float | null
	 */
	public function getProductPriceValue($productID){
		if( !array_key_exists($productID, $this->_arProductListIndex) ) {
			return $this->_arProductListIndex[$productID]['PRICE_VALUE'];
		}
		return null;
	}

	public function setProductPriceValue($productID, $priceValue) {
		$priceValue = floatval($priceValue);
		if($priceValue <= 0) {
			$this->addError(GetMessage('OBX_BASKET_ERROR_10'), 10);
			return false;
		}
		if( !array_key_exists($productID, $this->_arProductListIndex) ) {
			$this->addError(GetMessage('OBX_BASKET_ERROR_9'), 9);
			return false;
		}
		$bSuccess = self::$_BasketItemDBS->update(array(
			'ID' => $this->_arProductListIndex[$productID]['ID'],
			'PRICE_VALUE' => $priceValue,
		));
		if(!$bSuccess) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->addError(GetMessage('OBX_BASKET_ERROR_1100').' '.$arError['TEXT'], (1100 + $arError['CODE']));
			return false;
		}
		$this->_arProductListIndex[$productID]['PRICE_VALUE'] = $priceValue;
		return true;
	}

	public function getProductPrice($productID) {
		if( !array_key_exists($productID, $this->_arProductListIndex) ) {
			$priceValue = $this->_arProductListIndex[$productID]['PRICE_VALUE'];
			$priceID = intval($this->_arProductListIndex[$productID]['PRICE_ID']);
			$arPrice = array();
			if($priceID<1) {
				// TODO: вернуть массив c данными о типе цены, а так же PRICE_VALUE_FORMATTED
			}
		}
		return null;
	}

	/**
	 * Получить число позиций номенклатуры
	 * @return int
	 */
	public function getProductsCount() {
		$count = intval($this->_countProducts);
		return $count;
	}

	/**
	 * Получить количество единиц данного продукта
	 * @param int $productID
	 * @return float
	 */
	public function getProductQuantity($productID){
		if( !array_key_exists($productID, $this->_arItemsQuantity) ) {
			return 0;
		}
		return $this->_arItemsQuantity[$productID];
	}

	public function getQuantityList() {
		return $this->_arItemsQuantity;
	}

	/**
	 * Удалить товар из корзины
	 * @param int $productID
	 * @return bool
	 */
	public function removeProduct($productID){
		if( ! array_key_exists($productID, $this->_arProductListIndex) ) {
			$this->addWarning(GetMessage('OBX_BASKET_WARNING_2'));
			return true;
		}
		$bSuccess = self::$_BasketItemDBS->delete($this->_arProductListIndex[$productID]['ID']);
		if(!$bSuccess) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->addError(GetMessage('OBX_BASKET_ERROR_600').' '.$arError['TEXT'], (600 + $arError['CODE']));
			return false;
		}
		unset($this->_arProductList[$this->_arProductListIndex[$productID]['ID']]);
		unset($this->_arProductListIndex[$productID]);
		unset($this->_arItemsQuantity[$productID]);
		$this->_countProducts--;
		return true;
	}


	/**
	 * Добавить в корзину
	 * @param $productID - идентификатор элемента инфоблока
	 * @param int $quantity - количество / не менее 1
	 * @param null $priceValue - цена
	 * @param null $priceID - идентификатор цены
	 * @return int
	 */
	public function addProduct($productID, $quantity = 1, $priceValue = null, $priceID = null){
		$quantity = floatval($quantity);
		$quantity = ($quantity<=0)?1:$quantity;
		$productID = intval($productID);
		if($productID<1) {
			return -1;
		}
		if( $priceID !== null ) {
			$arAvailPriceList = self::$_PriceDBS->getAvailPriceForUser($this->_arFields['USER_ID']);
			if( ! in_array($priceID, $arAvailPriceList) ) {
				$this->addWarning('OBX_BASKET_WARNING_1', 1);
				$priceID = null;
			}
		}

		if( $priceValue !== null && floatval($priceValue) < 0) {
			$priceValue = null;
		}
		$arEmptyPriceError = null;
		if($priceValue === null) {
			$arOptimalPrice = Price::getOptimalProductPrice($productID, $this->_arFields['USER_ID']);
			if( empty($arOptimalPrice) ) {
				$arEmptyPriceError = array(
					'TYPE' => 'E',
					'TEXT' => GetMessage('OBX_BASKET_ERROR_5'),
					'CODE' => 5
				);
			}
			else {
				$priceValue = $arOptimalPrice['VALUE'];
				$priceID = $arOptimalPrice['PRICE_ID'];
			}
		}
		if( array_key_exists($productID, $this->_arItemsQuantity) ) {
			$newQuantity = $this->_arItemsQuantity[$productID] + $quantity;
			$bSuccess = self::$_BasketItemDBS->update(array(
				'BASKET_ID' => $this->_arFields['ID'],
				'PRODUCT_ID' => $productID,
				'QUANTITY' => $newQuantity
			));
			if(!$bSuccess) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				$this->addError(GetMessage('OBX_BASKET_ERROR_200').' '.$arError['TEXT'], (200 + $arError['CODE']));
				if($arEmptyPriceError !== null) {
					$this->addError($arEmptyPriceError['TEXT'], $arEmptyPriceError['CODE']);
				}
				return -1;
			}
			$this->_arItemsQuantity[$productID] = $newQuantity;
			$this->_arProductListIndex[$productID]['QUANTITY'] = $newQuantity;
		}
		else {
			$newID = self::$_BasketItemDBS->add(array(
				'BASKET_ID' => $this->_arFields['ID'],
				'PRODUCT_ID' => $productID,
				'QUANTITY' => $quantity,
				'PRICE_ID' => $priceID,
				'PRICE_VALUE' => $priceValue
			));
			if($newID < 1) {
				$arError = self::$_BasketItemDBS->popLastError('ARRAY');
				$this->addError(GetMessage('OBX_BASKET_ERROR_300').' '.$arError['TEXT'], (300 + $arError['CODE']));
				if($arEmptyPriceError !== null) {
					$this->addError($arEmptyPriceError['TEXT'], $arEmptyPriceError['CODE']);
				}
				return -1;
			}
			$this->_countProducts++;
			$this->syncProductList();
		}
		return $this->_arItemsQuantity[$productID];
	}

	/**
	 * Прибавить (отнять) к количеству определенного товара разницу (delta)
	 * Возвращает новое количество продукта в корзине
	 * @param $productID
	 * @param $delta
	 * @return int
	 */
	public function changeProductQuantity($productID, $delta){
		$delta = floatval($delta);
		$newQuantity = $this->_arItemsQuantity[$productID] + $delta;
		return $this->setProductQuantity($productID, $newQuantity);
	}

	/**
	 * Установить определенное количество единиц товара в корзине
	 * @param $productID
	 * @param $quantity
	 * @return float
	 */
	public function setProductQuantity($productID, $quantity){
		$quantity = floatval($quantity);
		if($quantity <= 0) {
			if( ! $this->removeProduct($productID) ) {
				return -1;
			}
			return 0;
		}
		if( !array_key_exists($productID, $this->_arProductListIndex) ) {
			$this->addError(GetMessage('OBX_BASKET_ERROR_8'), 8);
			return -1;
		}
		$bSuccess = self::$_BasketItemDBS->update(array(
			'ID' => $this->_arProductListIndex[$productID]['ID'],
			'QUANTITY' => $quantity,
		));
		if(!$bSuccess) {
			$arError = self::$_BasketItemDBS->popLastError('ARRAY');
			$this->addError(GetMessage('OBX_BASKET_ERROR_700').' '.$arError['TEXT'], (700 + $arError['CODE']));
			return -1;
		}
		$this->_arProductListIndex[$productID]['QUANTITY'] = $quantity;
		$this->_arItemsQuantity[$productID] = $quantity;
	}

	public function syncProductList() {
		if( $this->_arFields['ID'] < 1 ) {
			return false;
		}
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
			'TOTAL_PRICE_VALUE',
			//'VAT_ID',
			//'VAT_VALUE',
			'IB_ELT_ID',
			'IB_ELT_IBLOCK_ID',
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
		$arBasketItems = self::$_BasketItemDBS->getListArray(null, array('BASKET_ID' => $this->_arFields['ID']), null, null, $arSelect);

		// TODO: [Tashiro:2013-06-13] Надо разделить эту логику иначе создание заказа из корзины всегда будет с ошибкой
		/*if( empty($arBasketItems) ) {
			$this->addError(GetMessage('OBX_BASKET_ERROR_1'));
			return false;
		}*/
		$countProducts = 0;
		if( count($arBasketItems)>0 ) {
			foreach($arBasketItems as $key => &$arItem) {
				if($arItem['BASKET_ID'] != $this->_arFields['ID']) continue;
				$countProducts++;
				$arItemPriceList = Price::getProductPriceList($arItem['PRODUCT_ID'], $this->getFields('USER_ID'));
				$arItemPriceListIndex = Tools::getListIndex($arItemPriceList, 'PRICE_ID', true, true);
				$arBasketItem['PRICE_LIST'] = $arItemPriceListIndex;
				$arBasketItem['PRICE'] = array();
				if($arItem['PRICE_ID'] > 0) {
					$arItem['PRICE'] = $arItemPriceListIndex[$arItem['PRICE_ID']];
					if(
						$arItem['PRICE']['VALUE'] != $arItem['PRICE_VALUE']
						||
						$arItem['PRICE']['DISCOUNT_VALUE'] != $arItem['DISCOUNT_VALUE']
					) {
						$arFormat = array(
							'DEC_PRECISION' => $arItem['PRICE']['CURRENCY_DEC_PRECISION'],
							'DEC_POINT' => $arItem['PRICE']['CURRENCY_DEC_POINT'],
							'THOUSANDS_SEP' => $arItem['PRICE']['CURRENCY_THOUSANDS_SEP']
						);
						$arItem['PRICE']['VALUE'] = $arItem['PRICE_VALUE'];
						$arItem['PRICE']['VALUE_FORMATTED'] = self::$_CurrencyFormatDBS->formatPrice(
							$arItem['PRICE']['VALUE'],
							$arItem['PRICE']['CURRENCY'],
							$arItem['PRICE']['CURRENCY_LANG_ID'],
							$arFormat
						);
						$arItem['PRICE']['DISCOUNT_VALUE'] = $arItem['DISCOUNT_VALUE'];
						$arItem['PRICE']['DISCOUNT_VALUE_FORMATTED'] = self::$_CurrencyFormatDBS->formatPrice(
							$arItem['PRICE']['DISCOUNT_VALUE'],
							$arItem['PRICE']['CURRENCY'],
							$arItem['PRICE']['CURRENCY_LANG_ID'],
							$arFormat
						);
						$arItem['PRICE']['TOTAL_VALUE'] = $arItem['TOTAL_VALUE'];
						$arItem['PRICE']['TOTAL_VALUE_FORMATTED'] = self::$_CurrencyFormatDBS->formatPrice(
							$arItem['PRICE']['TOTAL_VALUE'],
							$arItem['PRICE']['CURRENCY'],
							$arItem['PRICE']['CURRENCY_LANG_ID'],
							$arFormat
						);
					}
				}
				else {
					$basketCurrency = $this->getFields('CURRENCY');
					$arItem['PRICE']['VALUE'] = $arItem['PRICE_VALUE'];
					$arItem['PRICE']['CURRENCY'] = $basketCurrency;
					$arItem['PRICE']['VALUE_FORMATTED'] = CurrencyFormatDBS::getInstance()->formatPrice(
						$arItem['PRICE']['VALUE'],
						$basketCurrency
					);
					$arItem['PRICE']['DISCOUNT_VALUE_FORMATTED'] = CurrencyFormatDBS::getInstance()->formatPrice(
						$arItem['PRICE']['DISCOUNT_VALUE'],
						$basketCurrency
					);
					$arItem['PRICE']['TOTAL_VALUE_FORMATTED'] = CurrencyFormatDBS::getInstance()->formatPrice(
						$arItem['PRICE']['TOTAL_VALUE'],
						$basketCurrency
					);
				}
				$arItem['IB_ELEMENT'] = array();
				foreach($arItem as $fldName => &$fldValue) {
					if( strpos($fldName, 'IB_ELT_') !== false ) {
						$arItem['IB_ELEMENT'][substr($fldName, 7)] = $fldValue;
						unset($arItem[$fldName]);
					}
				}

				$this->_arProductList[$arItem['ID']] = $arItem;
				$this->_arProductListIndex[$arItem['PRODUCT_ID']] = &$this->_arProductList[$arItem['ID']];
				$this->_arItemsQuantity[$arItem['PRODUCT_ID']] = $arItem['QUANTITY'];
			}
			$this->_countProducts = $countProducts;
		}
		return true;
	}

	protected function _getProductIBlockPropertyValues(&$productID, &$bExcludePriceProps, &$arPricePropLinks) {
		$arPropValues = array();
		$arItem = &$this->_arProductListIndex[$productID];
		$rsProps = \CIBlockElement::GetProperty($arItem['IB_ELEMENT']['IBLOCK_ID'], $arItem['IB_ELEMENT']['ID'], array("sort" => "asc"));
		while( $arProp = $rsProps->Fetch() ) {
			if( $bExcludePriceProps && array_key_exists($arProp['ID'], $arPricePropLinks) ) {
				continue;
			}
			if( !empty($arProp['CODE']) ) {
				$code = $arProp['CODE'];
			}
			else {
				$code = $arProp['ID'];
			}
			$arPropValues[$code] = $arProp;
		}
		return $arPropValues;
	}

	public function getProductIBlockPropertyValues($productID = null, $bExcludePriceProps = true) {
		$arPropValues = array();
		if(
			count($this->_arProductList)<1
			|| ($productID === null
				&& !array_key_exists($productID, $this->_arProductListIndex)
			)
		) {
			return $arPropValues;
		}
		if($bExcludePriceProps) {
			/**
			 * @var CIBlockPropertyPriceDBS $CIBPriceProp
			 */
			$CIBPriceProp = CIBlockPropertyPriceDBS::getInstance();
			$arPricePropLinksPlain = $CIBPriceProp->getListArray(array('IBLOCK_ID' => 'ASC'), array('!IBLOCK_ECOM_ID' => null));
			$arPricePropLinks = Tools::getListIndex($arPricePropLinksPlain, array('IBLOCK_PROP_ID'), true, true);
		}

		if($productID !== null) {
			$arPropValues = $this->_getProductIBlockPropertyValues($productID, $bExcludePriceProps, $arPricePropLinks);
		}
		else {
			foreach($this->_arProductListIndex as &$arItem) {
				$arPropValues[$arItem['PRODUCT_ID']] = $this->_getProductIBlockPropertyValues(
					$arItem['PRODUCT_ID'], $bExcludePriceProps, $arPricePropLinks);
			}
		}
		return $arPropValues;
	}



	/**
	 * Объединить корзины
	 * @param Basket $Basket4Merge - корзина, товары которой будут скопированы
	 * @param bool $bClearMergedBasket - очистить корзину $Basket4Merge
	 */
	public function mergeBasket(self $Basket4Merge, $bClearMergedBasket = false) {
		$arBasket4MergeProducts = $Basket4Merge->getProductsList();
		foreach($arBasket4MergeProducts as &$arItem) {
			if( array_key_exists($arItem['PRODUCT_ID'], $this->_arProductListIndex) ) {
				if($arItem['QUANTITY'] > $this->_arItemsQuantity[$arItem['PRODUCT_ID']]) {
					$this->setProductQuantity($arItem['PRODUCT_ID'], $arItem['QUANTITY']);
				}
			}
			else {
				$this->addProduct($arItem['PRODUCT_ID'], $arItem['QUANTITY'], $arItem['PRICE_VALUE'], $arItem['PRICE_ID']);
			}
		}
		if($bClearMergedBasket) {
			$Basket4Merge->clear();
		}
	}
}
