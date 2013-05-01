<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 **         Artem P. Morozov  aka tashiro     **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

IncludeModuleLangFile(__FILE__);


class OBX_Order extends OBX_CMessagePoolDecorator {
	protected $_OrderDBS = null;
	protected $_BasketItemDBS = null;
	protected $_OrderStatusDBS = null;
	protected $_OrderPropertyDBS = null;
	protected $_OrderCommentDBS = null;
	protected $_EComIBlockDBS = null;
	protected $_PriceDBS = null;
	protected $_CIBlockPropertyPriceDBS = null;
	protected $_Basket = null;

	protected $_arOrder = array();
	protected $_bFieldsChanged = true;

	// Кострутор объекта из БД или из ID заказа
	protected function __construct() {
		$this->_OrderDBS = OBX_OrderDBS::getInstance();
		$this->_OrderStatusDBS = OBX_OrderStatusDBS::getInstance();
		$this->_OrderPropertyDBS = OBX_OrderPropertyDBS::getInstance();
		$this->_OrderPropertyValuesDBS = OBX_OrderPropertyValuesDBS::getInstance();
		$this->_OrderCommentDBS = OBX_OrderCommentDBS::getInstance();
		$this->_BasketItemDBS = OBX_BasketItemDBS::getInstance();
		$this->_EComIBlockDBS = OBX_ECommerceIBlockDBS::getInstance();
		$this->_PriceDBS = OBX_PriceDBS::getInstance();
		$this->_CIBlockPropertyPriceDBS = OBX_CIBlockPropertyPriceDBS::getInstance();
		$this->_Basket = OBX_Basket::getInstance();
	}

	protected function __clone() {
	}

	public static function getList($arSort = null, $arFilter = null, $arGroupBy = null, $arPagination = null, $arSelect = null, $bShowNullFields = true) {
		$OrderList = OBX_OrderDBS::getInstance();
		$res = $OrderList->getList($arSort, $arFilter, $arGroupBy, $arPagination, $arSelect, $bShowNullFields);
		$OrderDBResult = new OBX_OrderDBResult($res, $arSelect);

		return $OrderDBResult;
	}

	public static function getListArray($arSort = null, $arFilter = null, $arGroupBy = null, $arPagination = null, $arSelect = null, $bShowNullFields = true) {
		$arResult = array();
		$res = self::getList($arSort, $arFilter, $arGroupBy, $arPagination, $arSelect, $bShowNullFields);
		while ($arOrder = $res->Fetch()) {
			$arResult[] = $arOrder;
		}
		return $arResult;
	}

	public static function getByID($orderID, $arSelect = null) {
		$OrderDBS = OBX_OrderDBS::getInstance();
		$rsOrder = $OrderDBS->getList(null, array('ID' => $orderID), null, null, $arSelect, false);
		$arOrder = $rsOrder->Fetch();
		if (empty($arOrder)) {
			return array();
		}
		return $arOrder;
	}

	public static function add($arFields = null, &$arErrors = array()) {
		$Order = new self;
		$Order->_OrderDBS->clearErrors();
		$newID = $Order->_OrderDBS->add($arFields);

		if ($newID <= 0) {
			$arErrors = $Order->_OrderDBS->getErrors();
			return null;
		}
		$bSuccess = $Order->read($newID);
		if (!$bSuccess) {
			$arErrors = $Order->getErrors();
			return null;
		}
		return $Order;
	}

	public static function delete($orderID) {
		$OrderDBS = OBX_OrderDBS::getInstance();
		$OrderDBS->delete($orderID);
	}

	public static function getOrder($ID, &$arErrors = array()) {
		$Order = new self;
		$bSuccess = $Order->read($ID);
		if (!$bSuccess) {
			$arErrors = $Order->getErrors();
			return null;
		}
		return $Order;
	}

	protected function read($orderID) {
		if ($orderID instanceof OBX_OrderDBResult) {
			$arOrder = $orderID->Fetch();
			if (isset($arOrder['ID'])) {
				$arOrder = $this->_OrderDBS->getByID($arOrder['ID']);
			}
		} elseif (is_numeric($orderID)) {
			$arOrder = $this->_OrderDBS->getByID($orderID);
		} elseif (!empty($orderID) && is_array($orderID)) {
			if (isset($orderID['ID']) && intval($orderID['ID']) > 0) {
				$arOrder = $this->_OrderDBS->getByID($orderID['ID']);
			}
		}

		if (empty($arOrder) || !is_array($arOrder)) {
			return false;
		}

		$this->_arOrder = $arOrder;
		$this->_bFieldsChanged = false;
		return true;
	}

	public function setBasketID($basketID) {
		$Basket = OBX_Basket::getInstance($basketID);
		if ($Basket !== null) {
			$this->_Basket = $Basket;
		}
	}

	public function getFields() {
		if ($this->_bFieldsChanged) {
			$this->read($this->_arOrder['ID']);
		}
		return $this->_arOrder;
	}

	public function setFields($arFields) {
		$arFields['ID'] = $this->_arOrder['ID'];

		if ($this->_OrderDBS->update($arFields)) {
			$this->_bFieldsChanged = true;
			return true;
		}
		return false;
	}

	/**
	 * Получить значения свойств заказа
	 * @return null
	 */
	public function getProperties($arSort = null, $arFilter = null, $arGroupBy = null, $arPagination = null, $arSelect = null, $bShowNullFields = true) {
		$arResult = array();

		if (is_array($arFilter)) {
			$arFilter['ORDER_ID'] = $this->_arOrder['ID'];
		} else {
			$arFilter = array('ORDER_ID' => $this->_arOrder['ID']);
		}
		$arProperties = $this->_OrderPropertyDBS->getListArray();

		$arOrderProperties = $this->_OrderPropertyValuesDBS->getListArray(
			$arSort, $arFilter, $arGroupBy, $arPagination, $arSelect, $bShowNullFields
		);
		// так как groupBy еще не работает временное решение
		$arOrderPropertiesTemp = array();
		foreach ($arOrderProperties as $arOrderProp) {
			$arOrderPropertiesTemp[$arOrderProp['PROPERTY_ID']] = $arOrderProp;
		}
		$arOrderProperties = $arOrderPropertiesTemp;

		foreach ($arProperties as $arProp) {
			$arResult[] = array_merge($arProp, $arOrderProperties[$arProp['ID']]);
		}

		return $arResult;
	}

	/**
	 * Задать значения свойств заказа
	 * @param type $arProperties
	 */
	public function setProperties($arProperties) {
		$arExistsPropValueLst = $this->_OrderPropertyValuesDBS->getListArray(
			null,
			array(
				'ORDER_ID' => $this->_arOrder['ID'],
			)
			, null
			, null
			, array(
				'ID', 'ORDER_ID', 'PROPERTY_ID', 'PROPERTY_CODE', 'PROPERTY_TYPE', 'VALUE'
			)
		);
		$bSetPropValuesSuccess = true;
		$bEvenOneUpdateSuccess = false;
		foreach ($arExistsPropValueLst as $valKey => &$arPropVal) {
			$bCreateNewPropValue = false;
			$bSuccess = false;
			$propValue = null;
			if (isset($arProperties[$arPropVal['PROPERTY_ID']])) {
				$propValue = $arProperties[$arPropVal['PROPERTY_ID']];
			} elseif (isset($arProperties[$arPropVal['PROPERTY_CODE']])) {
				$propValue = $arProperties[$arPropVal['PROPERTY_CODE']];
			}
			if ($propValue !== null && $propValue !== "null") {
				$arPropValueFields = array(
					'ORDER_ID' => $arPropVal['ORDER_ID'],
					'PROPERTY_ID' => $arPropVal['PROPERTY_ID'],
					'VALUE' => $propValue
				);
				if (!isset($arPropVal['ID']) || intval($arPropVal['ID']) <= 0) {
					$newPropValueID = $this->_OrderPropertyValuesDBS->add($arPropValueFields);
					$bSuccess = (intval($newPropValueID) > 0) ? true : false;
				} else {
					$bSuccess = $this->_OrderPropertyValuesDBS->update($arPropValueFields);
				}
				if (!$bSuccess) {
					$arError = $this->_OrderPropertyValuesDBS->popLastError('ARRAY');
					$this->addError($arError['TEXT'], $arError['CODE']);
					$bSetPropValuesSuccess = false;
				} else {
					$bEvenOneUpdateSuccess = true;
				}
			}
		}
		if ($bEvenOneUpdateSuccess) {
			$curTime = date('Y-m-d H:i:s');
			$this->_OrderDBS->update(array('ID' => $this->_arOrder['ID'], 'TIMESTAMP_X' => $curTime));
		}
		return $bSetPropValuesSuccess;
	}


	/**
	 * Получить текущее значение статуса заказа
	 * @return array
	 */
	public function getStatus() {
		if ($this->_bFieldsChanged) {
			$arOrder = $this->read($this->_arOrder['ID']);
			$arStatus = $this->_OrderStatusDBS->getByID($arOrder['STATUS_ID']);
			if (is_array($arStatus)) {
				$this->_arOrderStatus = $arStatus;
			}
			return $arStatus;
		} else {
			return $this->_arOrderStatus;
		}
	}

	/**
	 * Установить статуса заказа
	 * @param $statusVar
	 * @return bool
	 */
	public function setStatus($statusVar) {
		$arStatus = array();
		if (is_numeric($statusVar)) {
			$arStatus = $this->_OrderStatusDBS->getByID($statusVar);
		} else {
			$arStatus = $this->_OrderStatusDBS->getListArray(null, array('CODE' => $statusVar));
			if (is_array($arStatus)) {
				$arStatus = $arStatus[0];
			} else {
				$this->addError(GetMessage('OBX_ORDER_CLASS_ERROR_1'), 1);
				return false;
			}
		}
		if ($this->_OrderDBS->update(
			array(
				'ID' => $this->_arOrder['ID'],
				'STATUS_ID' => $arStatus['ID']
			))
		) {
			$this->_arOrder['STATUS_ID'] = $arStatus['ID'];
			$this->_arOrderStatus = $arStatus;

			$this->_bFieldsChanged = true;
			return true;
		}
		return false;
	}

	public function getItems() {
		return $this->_BasketItemDBS->getListArray(array('ID' => 'ASC'), array('ORDER_ID' => $this->_arOrder['ID']));
	}

	public function setItems($arItems, $bHardListSet = false, $bQuantityAdd = false) {
		/*
		$arItems = array(
			0 => array(
				'IBLOCK_ID' => $IblockID,
				'PRODUCT_ID' => $PRODUCT_ID,
				'PRODUCT_NAME' => 'STRING',
				'QUANTITY' => 1,
				'WEIGHT' => 2.12,
				'PRICE_ID' => 1,
				'PRICE_VALUE' => 18.50,
				'DISCOUNT_VALUE' => 18.50,
				'VAT_ID' => NULL,
				'VAT_VALUE' => 18.00
				)
			);
		*/
		global $DB;

		//$arEComIBlockList = $this->_EComIBlockDBS->getListArray();

		$arExistsOrderItems = array();
		$arExistsOrderItemsList = $this->_BasketItemDBS->getListArray(
			null,
			array('ORDER_ID' => $this->_arOrder['ID']),
			null, null
		//,array('ID', 'ORDER_ID', 'IBLOCK_ID', 'PRODUCT_ID', 'PRODUCT_NAME', 'QUANTITY')
		);
		$arExistsOrderItems = array();
		if (count($arExistsOrderItemsList) > 0) {
			foreach ($arExistsOrderItemsList as &$arExistsItem) {
				$arExistsOrderItems[$arExistsItem['PRODUCT_ID']] = array(
					'ID' => $arExistsItem['ID'],
					'PRODUCT_ID' => $arExistsItem['PRODUCT_ID'],
					'PRODUCT_NAME' => $arExistsItem['PRODUCT_NAME'],
					'QUANTITY' => $arExistsItem['QUANTITY'],
					'EXISTS_IN_ARGUMENT' => false,
				);
			}
		}
		unset($arExistsOrderItemsList);

		foreach ($arItems as $keyItem => $arFields) {
			if (isset($arFields['QUANTITY']) && $arFields['QUANTITY'] <= 0) {
				if (array_key_exists($arFields['PRODUCT_ID'], $arExistsOrderItems)) {
					$this->_BasketItemDBS->delete($arExistsOrderItems[$arFields['PRODUCT_ID']]['ID']);
				}
				continue;
			}
			if (!isset($arFields['PRICE_VALUE'])){
				$arOptimalPrice = $this->_PriceDBS->getOptimalProductPrice($arFields['PRODUCT_ID'], $this->_arOrder['USER_ID']);
				if (is_array($arOptimalPrice)) {
					$arFields['PRICE_ID'] = $arOptimalPrice['PRICE_ID'];
					$arFields['PRICE_VALUE'] = $arOptimalPrice['TOTAL_VALUE'];
				}
			}
			$arFields['ORDER_ID'] = $this->_arOrder['ID'];
			if (array_key_exists($arFields['PRODUCT_ID'], $arExistsOrderItems)) {
				if (array_key_exists('QUANTITY_ADD', $arFields) && $arFields['QUANTITY_ADD'] == 'Y'
					|| $bQuantityAdd
				) {
					$arFields['QUANTITY'] = $arFields['QUANTITY'] + $arExistsOrderItems[$arFields['PRODUCT_ID']]['QUANTITY'];
					unset($arFields['QUANTITY_ADD']);
				}
				$bSuccess = $this->_BasketItemDBS->update($arFields);
				$arExistsOrderItems[$arFields['PRODUCT_ID']]['EXISTS_IN_ARGUMENT'] = true;
			} else {
				$bCorrect = false;
				// стремное решение, надо добавить больше возможности в DBSimple
				// TODO: Find a better solution
				$sQuery = "SELECT b.IBLOCK_ID FROM b_iblock_element as a
					LEFT JOIN obx_ecom_iblock as b on(a.IBLOCK_ID = b.IBLOCK_ID)
					WHERE a.ID=".$arFields["PRODUCT_ID"];
				$res = $DB->Query($sQuery);
				$arIblock = $res->Fetch();
				if (is_array($arIblock) && !empty($arIblock) && !empty($arIblock["IBLOCK_ID"])){
					$bCorrect = true;
				}
				// ^^^
				if ($bCorrect){
					$newOrderItemID = $this->_BasketItemDBS->add($arFields);
					$bSuccess = ($newOrderItemID > 0) ? true : false;
				}else{
					$bSuccess = false;
					$this->addError(GetMessage('OBX_ORDER_CLASS_ERROR_NOT_ECONOM_IBLOCK'));
				}
			}
			if (!$bSuccess) {
				$arErrorsList = $this->_BasketItemDBS->getErrors();
				$this->_BasketItemDBS->getMessagePool()->addError(GetMessage('OBX_ORDER_CLASS_ERROR_2') . ': ' . implode("<br />\n", $arErrorsList), 2);
			}
		}

		if ($bHardListSet) {
			foreach ($arExistsOrderItems as &$arExistsItem) {
				if ($arExistsItem['EXISTS_IN_ARGUMENT'] == false) {
					$this->_BasketItemDBS->delete($arExistsItem['ID']);
				}
			}
		}
		$arEr = $this->getMessagePool()->getErrors();
		if (!empty($arEr)){
			$bSuccess = false;
		}
		$this->_bFieldsChanged = true;

		return $bSuccess;
	}

	public function getOrderCost($delivery = true) {

	}

	public function setProductListFromBasket() {
		$arItems = $this->_Basket->getItemsList();
		$arProducts = $this->_Basket->getProductsList();

	}
}
