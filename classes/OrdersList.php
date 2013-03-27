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

class OBX_OrdersDBS extends OBX_DBSimple {

	protected $_arTableList = array(
		'O' => 'obx_orders',
		'S' => 'obx_order_status',
		'I' => 'obx_order_items',
		'U' => 'b_user',
		//'OP' => 'obx_order_property',
		//'OPV' => 'obx_order_property_values'
	);

	protected $_arTableFields = array(
		'ID' => array('O' => 'ID'),
		'DATE_CREATED' => array('O' => 'DATE_CREATED'),
		'TIMESTAMP_X' => array('O' => 'TIMESTAMP_X'),
		'USER_ID' => array('O' => 'USER_ID'),
		'USER_NAME' => array('U' => 'CONCAT(U.LAST_NAME," ",U.NAME)'),
		'STATUS_ID' => array('O' => 'STATUS_ID'),
		'STATUS_CODE' => array('S' => 'CODE'),
		'STATUS_NAME' => array('S' => 'NAME'),
		'STATUS_DESCRIPTION' => array('S' => 'DESCRIPTION'),
		'CURRENCY' => array('O' => 'CURRENCY'),
//		'DELIVERY_ID' => array('O' => 'DELIVERY_ID'),
//		'DELIVERY_COST' => array('O' => 'DELIVERY_COST'),
//		'PAY_ID' => array('O' => 'PAY_ID'),
//		'PAY_TAX_VALUE' => array('O' => 'PAY_TAX_VALUE'),
//		'DISCOUNT_ID' => array('O' => 'DISCOUNT_ID'),
//		'DISCOUNT_VALUE' => array('O' => 'DISCOUNT_VALUE'),
		'ITEMS' => array('I' => 'GROUP_CONCAT(CONCAT("[",I.ID,"]"," ",I.PRODUCT_NAME," - ",I.QUANTITY) SEPARATOR "\n")'),
		'ITEMS_COST' => array('I' => 'SUM(I.PRICE_VALUE * I.QUANTITY)'),
//		'PROPERTIES_JSON' => array(
//			'OP' => '(SELECT
//						concat(
//							\'[\',
//							group_concat(
//								concat(\'{ PROPERTY_ID: "\', OP.ID, \'"\'),
//								concat(\', PROPERTY_TYPE: "\', OP.PROPERTY_TYPE, \'"\'),
//								concat(\', PROPERTY_NAME: "\', ,OP.NAME, \'"\'),
//								concat(\', PROPERTY_CODE: "\', OP.CODE, \'" }\')
//							),
//							\']\'
//						)
//					FROM
//						obx_order_property as OP,
//					LEFT JOIN
//						obx_order_property_values as OPV ON (OPV.PROPERTY_ID = OP.ID)
//					WHERE
//						OP.ORDER_ID = O.ID
//					GROUP BY
//						OP.ORDER_ID
//					)'
//		),
	);
	protected $_arTableLeftJoin = array(
		'S' => 'O.STATUS_ID = S.ID',
		'I' => 'O.ID = I.ORDER_ID',
		'U' => 'O.USER_ID = U.ID'
	);

	protected $_arGroupByFields = array(
		"O" => "ID"
	);

	protected $_arFieldsEditInAdmin = array(
		'USER_ID',
		'STATUS_ID',
	);
	protected $_arFieldsDescription = array();

	protected $_mainTable = 'O';
	protected $_mainTablePrimaryKey = 'ID';
	protected $_mainTableAutoIncrement = 'ID';

	protected $_arTableFieldsDefault = array();
	protected $_arSelectDefault = array(
		'ID',
		'DATE_CREATED',
		'TIMESTAMP_X',
		'USER_ID',
		'MODIFIED_BY',
		'USER_NAME',
		'STATUS_ID',
		'STATUS_CODE',
		'STATUS_NAME',
		'STATUS_DESCRIPTION',
		'CURRENCY',
		'ITEMS',
		'ITEMS_COST'
	);
	protected $_arSortDefault = array('ID' => 'ASC');

	function __construct() {
		global $USER;
		$this->_arTableFieldsDefault = array(
			'STATUS_ID' => '1',
			'CURRENCY' => OBX_Currency::getDefault(),
			'USER_ID' => $USER->GetID(),
			'MODIFIED_BY' => $USER->GetID(),
		);

		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'DATE_CREATED' => self::FLD_T_NO_CHECK,
			'TIMESTAMP_X' => self::FLD_T_NO_CHECK,
			'USER_ID' => self::FLD_T_USER_ID | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
			'STATUS_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
			'CURRENCY' => self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
			'DELIVERY_ID' => self::FLD_T_INT,
			'DELIVERY_COST' => self::FLD_T_FLOAT,
			'PAY_ID' => self::FLD_T_INT,
			'PAY_TAX_VALUE' => self::FLD_T_FLOAT,
			'DISCOUNT_ID' => self::FLD_T_INT,
			'DISCOUNT_VALUE' => self::FLD_T_FLOAT
		);

		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_CODE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_STATUS_ERROR_1'),
				'CODE' => 1
			),
			'REQ_FLD_NAME' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_STATUS_ERROR_2'),
				'CODE' => 1
			),
			'DUP_ADD_udx_obx_order_status' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_STATUS_ERROR_3'),
				'CODE' => 3
			),
			'DUP_UPD_udx_obx_order_status' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_STATUS_ERROR_4'),
				'CODE' => 4
			),
			'NOTHING_TO_DELETE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_STATUS_ERROR_6'),
				'CODE' => 6
			),
			'NOTHING_TO_UPDATE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_STATUS_ERROR_7'),
				'CODE' => 7
			)
		);
		$this->_arFieldsDescription = array(
			'ID' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_ID_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_ID_DESCR"),
			),
			'DATE_CREATED' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_DATE_CREATED_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_DATE_CREATED_DESCR"),
			),
			'TIMESTAMP_X' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_TIMESTAMP_X_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_TIMESTAMP_X_DESCR"),
			),
			'USER_ID' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_USER_ID_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_USER_ID_DESCR"),
			),
			'STATUS_ID' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_STATUS_ID_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_STATUS_ID_DESCR"),
			),
			'CURRENCY' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_CURRENCY_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_CURRENCY_DESCR"),
			),
			'ITEMS' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_ITEMS_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_ITEMS_DESCR"),
			),
			'ITEMS_COST' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_ITEMS_COST_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_ITEMS_COST_DESCR"),
			),
		);
	}

	protected function _onStartAdd(&$arFields) {
		$curTime = date('Y-m-d H:i:s');
		$arFields['DATE_CREATED'] = $curTime;
		return true;
	}

	protected function _onStartUpdate(&$arFields) {
		if (array_key_exists('DATE_CREATED', $arFields)) {
			unset($arFields['DATE_CREATED']);
		}
		return true;
	}

	protected function _onAfterDelete(&$arOrder) {
		$arFilter = array("ORDER_ID" => $arOrder["ID"]);
		OBX_OrderPropertyValuesDBS::getInstance()->deleteByFilter($arFilter);
		OBX_OrderItemsDBS::getInstance()->deleteByFilter($arFilter);

		return true;
	}

	public function add($arFields = array()) {
		return parent::add($arFields);
	}
}

class OBX_OrdersList extends OBX_DBSimpleStatic {
	static public function add($arFields = array()) {
		return parent::add($arFields);
	}
}
OBX_OrdersList::__initDBSimple(OBX_OrdersDBS::getInstance());
