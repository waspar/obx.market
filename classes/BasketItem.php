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

class OBX_BasketItemDBS extends OBX_DBSimple {
	protected $_arTableList = array(
		'I'		=> 'obx_basket_items',
		'O'		=> 'obx_orders',
		'V'		=> 'obx_visitors',
		'P'		=> 'obx_price',
		'IBE'	=> 'b_iblock_element',
		'IB'	=> 'b_iblock',
		'IBS'	=> 'b_iblock_section',
		'SI'	=> 'b_lang'
	);
	protected $_arTableLinks = array(
		0 => array(
			array("I" => "ORDER_ID"),
			array("O" => "ID")
		),
		1 => array(
			array("I" => "PRICE_ID"),
			array("P" => "ID")
		),
		2 => array(
			array('I' => 'PRODUCT_ID'),
			array('IBE' => 'ID'),
		)
	);
	protected $_arTableLeftJoin = array(
		'O'		=> 'O.ID = I.ORDER_ID',
		'V'		=> 'V.ID = I.VISITOR_ID',
		'P'		=> 'P.ID = I.PRICE_ID',
		'IBE'	=> 'I.PRODUCT_ID = IBE.ID',
		'IBS'	=> 'IBE.IBLOCK_SECTION_ID = IBS.ID',
		'IB'	=> 'IBE.IBLOCK_ID = IB.ID',
		'SI'	=> 'IB.LID = SI.LID'
	);
	protected $_arTableFields = array(
		'ID'						=> array('I'	=> 'ID'),
		'ORDER_ID'					=> array('I'	=> 'ORDER_ID'),
		'ORDER_USER_ID'				=> array('O'	=> 'USER_ID'),
		'VISITOR_ID'				=> array('V'	=> 'ID'),
		'VISITOR_USER_ID'			=> array('V'	=> 'USER_ID'),
		'PRODUCT_ID'				=> array('I'	=> 'PRODUCT_ID'),
		'PRODUCT_NAME'				=> array('I'	=> 'PRODUCT_NAME'),
		'QUANTITY'					=> array('I'	=> 'QUANTITY'),
		'WEIGHT'					=> array('I'	=> 'WEIGHT'),
		'PRICE_ID'					=> array('I'	=> 'PRICE_ID'),
		'PRICE_CODE'				=> array('P'	=> 'CODE'),
		'PRICE_NAME'				=> array('P'	=> 'NAME'),
		'PRICE_VALUE'				=> array('I'	=> 'PRICE_VALUE'),
		'DISCOUNT_VALUE'			=> array('I'	=> 'DISCOUNT_VALUE'),
		'VAT_ID'					=> array('I'	=> 'VAT_ID'),
		'VAT_VALUE'					=> array('I'	=> 'VAT_VALUE'),
		'IB_ELT_ID'					=> array('IBE'	=> 'ID'),
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
		'IB_ELT_SITE_ID'			=> array('SI'	=> 'LID'),
		'IB_ELT_SITE_DIR'			=> array('SI'	=> 'DIR')
	);
	protected $_arSelectDefault = array(
		'ID',
		'ORDER_ID',
		'VISITOR_ID',
		'PRODUCT_ID',
		'PRODUCT_NAME',
		'QUANTITY',
		'WEIGHT',
		'PRICE_ID',
		'PRICE_VALUE'
	);
	protected $_mainTable = 'I';
	protected $_mainTablePrimaryKey = 'ID';
	protected $_mainTableAutoIncrement = 'ID';
	protected $_arTableUnique = array(
		'udx_obx_basket_items' => array('ORDER_ID', 'VISITOR_ID', 'PRODUCT_ID')
	);
	protected $_arSortDefault = array('ID' => 'ASC');
	protected $_arTableFieldsDefault = array(
		'QUANTITY' => '1',
		'DELAYED' => 'N'
	);

	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID'				=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'ORDER_ID'			=> self::FLD_T_INT | self::FLD_CUSTOM_CK,
			'VISITOR_ID'		=> self::FLD_T_INT | self::FLD_CUSTOM_CK,
			'PRODUCT_ID'		=> self::FLD_T_IBLOCK_ELEMENT_ID | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'PRODUCT_NAME'		=> self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'QUANTITY'			=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'DELAYED'			=> self::FLD_T_BCHAR | self::FLD_NOT_NULL,
			'WEIGHT'			=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'PRICE_ID'			=> self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_REQUIRED | self::FLD_CUSTOM_CK | self::FLD_BRK_INCORR,
			'PRICE_VALUE'		=> self::FLD_T_FLOAT | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'DISCOUNT_VALUE'	=> self::FLD_T_FLOAT | self::FLD_NOT_NULL,
			'VAT_ID'			=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'VAT_VALUE'			=> self::FLD_T_FLOAT | self::FLD_NOT_NULL
		);
		$this->_arDBSimpleLangMessages = array(
//			'REQ_FLD_ORDER_ID' => array(
//				'TYPE' => 'E',
//				'TEXT' => GetMessage('OBX_ORDER_ITEMS_ERROR_1'),
//				'CODE' => 1
//			),
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
	}

	public function __check_PRICE_ID(&$fieldValue, &$arCheckData = null) {
		$DBPrice = OBX_PriceDBS::getInstance();
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
	public function __check_IBLOCK_ID(&$fieldValue, &$arCheckData = null) {
		$DBEComIB = OBX_ECommerceIBlockDBS::getInstance();
		$arEComIBlock = $DBEComIB->getByID($fieldValue);
		if( empty($arEComIBlock) || !is_array($arEComIBlock) ) {
			if($arCheckData !== null) {
				$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_9'), 9);
			}
			return false;
		}
		$arCheckData = $arEComIBlock;
		return true;
	}
	public function __check_VISITOR_ID(&$fieldValue, &$arCheckData = null) {
		$VisitorsDBS = OBX_VisitorDBS::getInstance();
		$arVisitor = $VisitorsDBS->getByID($fieldValue);
		if( empty($arVisitor) ) {
			return false;
		}
		return true;
	}

	protected function _onBeforeAdd(&$arFields, &$arCheckData) {
		if( empty($arFields['ORDER_ID']) && empty($arFields['VISITOR_ID']) ) {
			$this->addError(GetMessage('OBX_ORDER_ITEMS_ERROR_1'));
			return false;
		}
		if(
			empty($arFields['PRODUCT_NAME'])
			&& isset($arCheckData['PRODUCT_ID']['IS_CORRECT'])
			&& $arCheckData['PRODUCT_ID']['IS_CORRECT']
		) {
			$arFields['PRODUCT_NAME'] = $arCheckData['PRODUCT_ID']['CHECK_DATA']['NAME'];
		}
		return true;
	}

	public function update($arFields) {
		// передаем параметр $bNotUpdateUniqueFields = true
		// исключаем тем самым дублирование записей при обновлении товара в заказе
		return parent::update($arFields, true);
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

class OBX_BasketItem extends OBX_DBSimpleStatic {
	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}

	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}

OBX_BasketItem::__initDBSimple(OBX_BasketItemDBS::getInstance());