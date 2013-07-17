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
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;


IncludeModuleLangFile(__FILE__);

class BasketItemDBS extends DBSimple
{
	protected $_entityModuleID = 'obx.market';
	protected $_entityEventsID = 'BasketItem';

	protected $_arTableList = array(
		'I'		=> 'obx_basket_items',
		'B'		=> 'obx_basket',
		'O'		=> 'obx_orders',
		'P'		=> 'obx_price',
		'IBE'	=> 'b_iblock_element',
		'IB'	=> 'b_iblock',
		'IBS'	=> 'b_iblock_section',
		'SI'	=> 'b_lang'
	);
	protected $_arTableLinks = array(
		0 => array(
			array("I" => "BASKET_ID"),
			array("B" => "ID")
		),
		1 => array(
			array('B' => 'ORDER_ID'),
			array('O' => 'ID')
		),
		2 => array(
			array("I" => "PRICE_ID"),
			array("P" => "ID")
		),
		3 => array(
			array('I' => 'PRODUCT_ID'),
			array('IBE' => 'ID'),
		)
	);
	protected $_arTableLeftJoin = array(
		'B'		=> 'B.ID = I.BASKET_ID',
		'O'		=> 'O.ID = B.ORDER_ID',
		'P'		=> 'P.ID = I.PRICE_ID',
		'IBE'	=> 'I.PRODUCT_ID = IBE.ID',
		'IBS'	=> 'IBE.IBLOCK_SECTION_ID = IBS.ID',
		'IB'	=> 'IBE.IBLOCK_ID = IB.ID',
		'SI'	=> 'IB.LID = SI.LID'
	);
	protected $_arTableFields = array(
		'ID'						=> array('I'	=> 'ID'),
		'BASKET_ID'					=> array('I'	=> 'BASKET_ID'),
		'ORDER_ID'					=> array('B'	=> 'ORDER_ID'),
		'BASKET_USER_ID'			=> array('B'	=> 'USER_ID'),
		'ORDER_USER_ID'				=> array('O'	=> 'USER_ID'),
		'USER_ID'					=> array('B'	=> <<<SQL
			(SELECT
				IF B.ORDER_ID IS NULL
				THEN B.USER_ID
				ELSE O.USER_ID
			)
SQL
			, 'REQUIRED_TABLES' => 'O'
		),
		'PRODUCT_ID'				=> array('I'	=> 'PRODUCT_ID'),
		'PRODUCT_NAME'				=> array('I'	=> 'PRODUCT_NAME'),
		'QUANTITY'					=> array('I'	=> 'QUANTITY'),
		'WEIGHT'					=> array('I'	=> 'WEIGHT'),
		'PRICE_ID'					=> array('I'	=> 'PRICE_ID'),
		'PRICE_CODE'				=> array('P'	=> 'CODE'),
		'PRICE_NAME'				=> array('P'	=> 'NAME'),
		'PRICE_VALUE'				=> array('I'	=> 'PRICE_VALUE'),
		'DISCOUNT_VALUE'			=> array('I'	=> 'DISCOUNT_VALUE'),
		'TOTAL_PRICE_VALUE'			=> array('I'	=> 'TOTAL_PRICE_VALUE'),
		'VAT_ID'					=> array('I'	=> 'VAT_ID'),
		'VAT_VALUE'					=> array('I'	=> 'VAT_VALUE'),
		'IB_ELT_ID'					=> array('IBE'	=> 'ID'),
		'IB_ELT_IBLOCK_ID'			=> array('IBE'	=> 'IBLOCK_ID'),
		'IB_ELT_NAME'				=> array('IBE'	=> 'NAME'),
		'IB_ELT_CODE'				=> array('IBE'	=> 'CODE'),
		'IB_ELT_SECTION_ID'			=> array('IBE'	=> 'IBLOCK_SECTION_ID'),
		'IB_ELT_SECTION_CODE'		=> array('IBS'	=> 'CODE'),
		'IB_ELT_SORT'				=> array('IBE'	=> 'SORT'),
		'IB_ELT_PREVIEW_TEXT'		=> array('IBE'	=> 'PREVIEW_TEXT'),
		'IB_ELT_PREVIEW_PICTURE'	=> array('IBE'	=> 'PREVIEW_PICTURE'),
		'IB_ELT_DETAIL_TEXT'		=> array('IBE'	=> 'DETAIL_TEXT'),
		'IB_ELT_DETAIL_PICTURE'		=> array('IBE'	=> 'DETAIL_PICTURE'),
		'IB_ELT_XML_ID'				=> array('IBE'	=> 'XML_ID'),
		'IB_ELT_TIMESTAMP_X'		=> array('IBE'	=> 'TIMESTAMP_X'),
		'IB_ELT_MODIFIED_BY'		=> array('IBE'	=> 'MODIFIED_BY'),
		'IB_ELT_LIST_PAGE_URL'		=> array('IB'	=> 'DETAIL_PAGE_URL'),
		'IB_ELT_SECTION_PAGE_URL'	=> array('IB'	=> 'DETAIL_PAGE_URL'),
		'IB_ELT_DETAIL_PAGE_URL'	=> array('IB'	=> 'DETAIL_PAGE_URL'),
		'IB_ELT_SITE_ID'			=> array('SI'	=> 'LID', 'REQUIRED_TABLES' => array('IBE', 'IB')),
		'IB_ELT_SITE_DIR'			=> array('SI'	=> 'DIR', 'REQUIRED_TABLES' => array('IBE', 'IB'))
	);
	protected $_arSelectDefault = array(
		'ID',
		'ORDER_ID',
		'BASKET_ID',
		'PRODUCT_ID',
		'PRODUCT_NAME',
		'QUANTITY',
		'WEIGHT',
		'PRICE_ID',
		'PRICE_VALUE',
		'DISCOUNT_VALUE',
		'TOTAL_PRICE_VALUE'
	);
	protected $_mainTable = 'I';
	protected $_mainTablePrimaryKey = 'ID';
	protected $_mainTableAutoIncrement = 'ID';
	protected $_arTableUnique = array(
		'udx_obx_basket_items' => array('BASKET_ID', 'PRODUCT_ID', 'PRICE_ID')
	);
	protected $_arSortDefault = array('ID' => 'ASC');
	protected $_arTableFieldsDefault = array(
		'QUANTITY' => '1',
		'DELAYED' => 'N'
	);

	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID'				=> self::FLD_T_PK_ID,
			'BASKET_ID'			=> self::FLD_T_PK_ID | self::FLD_CUSTOM_CK,
			// Это поле реально отсутствует в основной таблице, обязательно удалять в событиях, после использования
			'ORDER_ID'			=> self::FLD_T_PK_ID | self::FLD_CUSTOM_CK,

			'PRODUCT_ID'		=> self::FLD_T_IBLOCK_ELEMENT_ID | self::FLD_REQUIRED,
			'PRODUCT_NAME'		=> self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_NOT_ZERO | self::FLD_REQUIRED,
			'QUANTITY'			=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'DELAYED'			=> self::FLD_T_BCHAR | self::FLD_NOT_NULL,
			'WEIGHT'			=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'PRICE_ID'			=> self::FLD_T_PK_ID
									| self::FLD_CUSTOM_CK
									| self::FLD_BRK_INCORR,

			'PRICE_VALUE'		=> self::FLD_T_FLOAT,
			'DISCOUNT_VALUE'	=> self::FLD_T_FLOAT,
			'VAT_ID'			=> self::FLD_T_INT,
			'VAT_VALUE'			=> self::FLD_T_FLOAT
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_IBLOCK_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_2'),
				'CODE' => 2
			),
			'REQ_FLD_PRODUCT_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_3'),
				'CODE' => 3
			),
			'REQ_FLD_PRODUCT_NAME' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_4'),
				'CODE' => 4
			),
			'DUP_ADD_udx_obx_basket_items' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_5'),
				'CODE' => 6
			),
			'NOTHING_TO_DELETE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_6'),
				'CODE' => 6
			),
			'NOTHING_TO_UPDATE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_7'),
				'CODE' => 7
			)
		);
		$this->_arFieldsDescription = array(
			'ID' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_ID_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_ID_DESCR"),
			),
			'PRODUCT_ID' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_PRODUCT_ID_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_PRODUCT_ID_DESCR"),
			),
			'PRODUCT_NAME' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_PRODUCT_NAME_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_PRODUCT_NAME_DESCR"),
			),
			'QUANTITY' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_QUANTITY_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_QUANTITY_DESCR"),
			),
			'DELAYED' => array(
				'NAME' => GetMessage('OBX_ORDERITEM_DELAYED_NAME'),
				'DESCRIPTION' => GetMessage('OBX_ORDERITEM_DELAYED_DESCR')
			),
			'WEIGHT' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_WEIGHT_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_WEIGHT_DESCR"),
			),
			'PRICE_ID' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_PRICE_ID_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_PRICE_ID_DESCR"),
			),
			'PRICE_NAME' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_PRICE_NAME_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_PRICE_NAME_DESCR"),
			),
			'PRICE_VALUE' => array(
				"NAME" => GetMessage("OBX_ORDERITEM_PRICE_VALUE_NAME"),
				"DESCRIPTION" => GetMessage("OBX_ORDERITEM_PRICE_VALUE_DESCR"),
			),

		);
		$this->_getEntityEvents();
	}

	public function __check_PRICE_ID(&$fieldValue, &$arCheckData = null) {
		$DBPrice = PriceDBS::getInstance();
		$arPrice = $DBPrice->getByID($fieldValue);
		if( empty($arPrice) || !is_array($arPrice) ) {
			if($arCheckData !== null) {
				$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_8'), 8);
			}
			return false;
		}
		$arCheckData = $arPrice;
		return true;
	}
//	public function __check_IBLOCK_ID(&$fieldValue, &$arCheckData = null) {
//		$arECommerceIBlocks = OBX_ECommerceIBlock::getCachedList();
//		if( !array_key_exists($fieldValue, $arECommerceIBlocks) ) {
//			$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_9'), 9);
//			return false;
//		}
//		return true;
//	}
	public function __check_BASKET_ID(&$fieldValue, &$arCheckData = null) {
		$arBasket = BasketDBS::getInstance()->getByID($fieldValue);
		if( empty($arBasket) ) {
			return false;
		}
		$arCheckData = $arBasket;
		return true;
	}

	public function __check_ORDER_ID(&$value, &$arCheckData = null) {
		$arOrder = OrderDBS::getInstance()->getByID($value);
		if( empty($arOrder) ) {
			return false;
		}
		$arCheckData = $arOrder;
		return true;
	}

	protected function _onStartAdd(&$arFields) {
		if( array_key_exists('PRICE_ID', $arFields) && $arFields['PRICE_ID'] == null) {
			unset($arFields['PRICE_ID']);
		}
		if( array_key_exists('TOTAL_PRICE_VALUE', $arFields) ) {
			unset($arFields['TOTAL_PRICE_VALUE']);
		}
		return parent::_onStartAdd($arFields);
	}

	protected function _onStartUpdate(&$arFields){
		if( array_key_exists('TOTAL_PRICE_VALUE', $arFields) ) {
			unset($arFields['TOTAL_PRICE_VALUE']);
		}
		return parent::_onStartUpdate($arFields);
	}

	protected function _onBeforeAdd(&$arFields, &$arCheckData) {
		if($arCheckData['PRODUCT_ID']['IS_CORRECT']) {
			$arECommerceIBlocks = ECommerceIBlock::getCachedList();
			if( !array_key_exists($arCheckData['PRODUCT_ID']['CHECK_DATA']['IBLOCK_ID'], $arECommerceIBlocks) ) {
				$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_9'), 9);
				return false;
			}
			if(empty($arFields['PRODUCT_NAME'])) {
				$arFields['PRODUCT_NAME'] = $arCheckData['PRODUCT_ID']['CHECK_DATA']['NAME'];
			}
		}
		$defaultCurrency = null;
		if(
			!array_key_exists('PRICE_VALUE', $arFields)
			||
			!is_numeric($arFields['PRICE_VALUE'])
			||
			$arFields['PRICE_VALUE'] < 0
		) {
			$arPricePropList = CIBlockPropertyPriceDBS::getInstance()->getListArray(array(
				'PRICE_ID' => $arFields['PRICE_ID'],
				'IBLOCK_ID' => $arCheckData['PRODUCT_ID']['CHECK_DATA']['IBLOCK_ID']
			));
			if( empty($arPricePropList) ) {
				$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_10'), 10);
				return false;
			}
			$arPriceProp = $arPricePropList[0];
			$rsPricePropValueList = \CIBlockElement::GetProperty(
				$arPriceProp['IBLOCK_ID'],
				$arFields['PRODUCT_ID'],
				array('SORT' => 'ASC'),
				array(
					'ID' => $arPricePropList[0]['IBLOCK_PROP_ID']
				)
			);
			if(
				!($arPricePropValue = $rsPricePropValueList->GetNext())
				||
				empty($arPricePropValue['VALUE'])
			) {
				$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_10'), 10);
				return false;
			}
			$defaultCurrency = $arPriceProp['CURRENCY'];
		}
		if( empty($arFields['BASKET_ID']) && empty($arFields['ORDER_ID']) ) {
			$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_1'), 1);
			return false;
		}
		elseif(empty($arFields['BASKET_ID']) && !empty($arFields['ORDER_ID'])) {
			if($defaultCurrency === null) {
				$defaultCurrency = Currency::getDefault();
			}
			$BasketDBS = BasketDBS::getInstance();
			$arBasketList = $BasketDBS->getListArray(null, array('ORDER_ID' => $arFields['ORDER_ID']));
			if( empty($arBasketList) ) {
				$basketOrderID = $BasketDBS->add(array(
					'ORDER_ID' => $arFields['ORDER_ID'],
					'CURRENCY' => $defaultCurrency
				));
				if(!$basketOrderID) {
					$arError = $BasketDBS->popLastError('ARRAY');
					$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_11', array(
						'#ERROR_TEXT#' => $arError['TEXT'].'; '.GetMessage('OBX_BASKET_ITEM_ERROR_CODE').': '.$arError['CODE'].'.'
					)), 11);
					return false;
				}
				$arFields['BASKET_ID'] = $basketOrderID;
			}
			else {
				$arFields['BASKET_ID'] = $arBasketList[0]['ID'];
			}
			unset($arFields['ORDER_ID']);
			unset($arCheckData['ORDER_ID']);
		}
		$weightPropID = intval($arECommerceIBlocks[$arCheckData['PRODUCT_ID']['CHECK_DATA']['IBLOCK_ID']]['WEIGHT_VAL_PROP_ID']);
		$discountPropID = intval($arECommerceIBlocks[$arCheckData['PRODUCT_ID']['CHECK_DATA']['IBLOCK_ID']]['DISCOUNT_VAL_PROP_ID']);
		if(
			(
				!array_key_exists('DISCOUNT_VALUE', $arFields)
				|| floatval($arFields['DISCOUNT_VALUE'])==0
			)
			&& $discountPropID > 0
		) {
			$discountPercentValue = 0;
			$rsDiscountPropValueList = \CIBlockElement::GetProperty(
				$arCheckData['PRODUCT_ID']['CHECK_DATA']['IBLOCK_ID'],
				$arFields['PRODUCT_ID'],
				array('SORT' => 'ASC'),
				array(
					'ID' => $discountPropID
				)
			);
			if( $arDiscountPropValue = $rsDiscountPropValueList->GetNext() ) {
				$discountPercentValue = intval($arDiscountPropValue['VALUE']);
				if( $discountPercentValue >= 100) {
					$discountPercentValue = 100;
					$arFields['DISCOUNT_VALUE'] = $arFields['PRICE_VALUE'];
				}
				else {
					$arFields['DISCOUNT_VALUE'] = ($arFields['PRICE_VALUE'] * round($discountPercentValue/100, 2));
				}
				$debug=1;
			}
			else {
				$discountPercentValue = 0;
				$arFields['DISCOUNT_VALUE'] = 0;
			}
		}
		if(
			(
				!array_key_exists('WEIGHT', $arFields)
				|| floatval($arFields['WEIGHT'])==0
			)
			&& $weightPropID > 0
		) {
			$weightValue = 0;
			$rsWeightPropValueList = \CIBlockElement::GetProperty(
				$arCheckData['PRODUCT_ID']['CHECK_DATA']['IBLOCK_ID'],
				$arFields['PRODUCT_ID'],
				array('SORT' => 'ASC'),
				array(
					'ID' => $weightPropID
				)
			);
			if( $arWeightPropValue = $rsWeightPropValueList->GetNext() ) {
				$arFields['WEIGHT'] = intval($arWeightPropValue['VALUE']);
			}
		}
		$arFields['TOTAL_PRICE_VALUE'] = floatVal($arFields['PRICE_VALUE'] - $arFields['DISCOUNT_VALUE']);
		if($arFields['TOTAL_PRICE_VALUE'] < 0) {
			$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_12_13'), 12);
			return false;
		}
		return parent::_onBeforeAdd($arFields, $arCheckData);
	}

	protected function _onBeforeExecUpdate(&$arFields, &$arCheckData = null) {
		if($arCheckData !== null) {
		// +++ [pronix] try to change PRODUCT_ID or BASKET_ID
			// DBSimple::update() выбросил из $arFields поля входящие в уникальный индекс
			// см. переопределенный метод update($arFields, $bNotUpdateUniqueFields)
			// аргумент $bNotUpdateUniqueFields = true
		 	// Тем не менее стоит выбросить предупреждение о попытке обновления PRODUCT_ID и BASKET_ID
			// А при использовании OBX_MAGIC_WORD можно и ошибку бросить, что бы наверняка пресечь Duplicate Entry в Mysql

			if( array_key_exists('PRODUCT_ID', $arCheckData)
				&& $arCheckData['PRODUCT_ID']['IS_CORRECT'] == true
				&& $arCheckData['__EXIST_ROW']['PRODUCT_ID'] != $arCheckData['PRODUCT_ID']['VALUE']
			) {
				if($arCheckData['__MAGIC_WORD']) {
					$this->addError(GetMessage('OBX_BASKET_ITEM_WARNING_1'), 31);
					return false;
				}
				$this->addWarning(GetMessage('OBX_BASKET_ITEM_WARNING_1'), 1);
			}
			if( array_key_exists('BASKET_ID', $arCheckData)
				&& $arCheckData['BASKET_ID']['IS_CORRECT'] == true
				&& $arCheckData['__EXIST_ROW']['BASKET_ID'] != $arCheckData['BASKET_ID']['VALUE']
			) {
				if($arCheckData['__MAGIC_WORD']) {
					$this->addError(GetMessage('OBX_BASKET_ITEM_WARNING_2'), 32);
					return false;
				}
				$this->addWarning(GetMessage('OBX_BASKET_ITEM_WARNING_2'), 2);
			}
		// ^^^ try to change PRODUCT_ID or BASKET_ID

		// +++ [pronix] check TOTAL_PRICE_VALUE
			//проверяем корректность значение скидки/наценки

			$priceValue = $arCheckData['__EXIST_ROW']['PRICE_VALUE'];
			$discountValue = $arCheckData['__EXIST_ROW']['DISCOUNT_VALUE'];

			if( array_key_exists('PRICE_VALUE', $arCheckData)
				&& $arCheckData['PRICE_VALUE']['IS_CORRECT'] == true
				&& $priceValue != $arCheckData['PRICE_VALUE']['VALUE']
			) {
				$priceValue = $arCheckData['PRICE_VALUE']['VALUE'];
			}

			if( array_key_exists('DISCOUNT_VALUE', $arCheckData)
				&& $arCheckData['DISCOUNT_VALUE']['IS_CORRECT'] == true
				&& $discountValue != $arCheckData['DISCOUNT_VALUE']['VALUE']
			) {
				$discountValue = $arCheckData['DISCOUNT_VALUE']['VALUE'];
			}
			$arFields['TOTAL_PRICE_VALUE'] = floatVal($priceValue - $discountValue);
			if( $arFields['TOTAL_PRICE_VALUE'] < 0 ) {
				$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_12_13'), 13);
				return false;
			}
		// ^^^ check TOTAL_PRICE_VALUE
		}
		return parent::_onBeforeExecUpdate($arFields, $arCheckData);
	}

	public function update($arFields) {
		// [pronix]
		// передаем параметр $bNotUpdateUniqueFields = true
		// исключаем тем самым дублирование записей при обновлении товара в заказе
		// а так же исключаем возможность поменять у товара корзины собственно PRODUCT_ID и BASKET_ID
		// однако предусмотрим явное задание через OBX_MAGIC_WORD
		if( array_key_exists(OBX_MAGIC_WORD, $arFields)) {
			return parent::update($arFields, false);
		}
		else {
			return parent::update($arFields, true);
		}

	}

	/*public function onIBlockDelete($IBLOCK_ID) {
		$this->deleteByFilter(array('IBLOCK_ID' => $IBLOCK_ID));
		$this->clearErrors();
	}
	public function onIBlockElementDelete($PRODUCT_ID) {
		$this->deleteByFilter(array('PRODUCT_ID' => $PRODUCT_ID));
		$this->clearErrors();
	}*/
	public function registerModuleDependencies() {
		/*	RegisterModuleDependences(
				'iblock', 'OnIBlockDelete',
				'obx.market',
				__CLASS__, 'onIBlockDelete', 610);
			RegisterModuleDependences(
				'iblock', 'OnIBlockElementDelete',
				'obx.market',
				__CLASS__, 'onIBlockElementDelete', 620);*/
	}

	public function unRegisterModuleDependencies() {
		/*		UnRegisterModuleDependences(
					'iblock', 'OnIBlockDelete',
					'obx.market',
					__CLASS__, 'onIBlockDelete');
				UnRegisterModuleDependences(
					'iblock', 'OnIBlockElementDelete',
					'obx.market',
					__CLASS__, 'onIBlockElementDelete');*/
	}
}

class BasketItem extends DBSimpleStatic {
	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}

	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}

BasketItem::__initDBSimple(BasketItemDBS::getInstance());