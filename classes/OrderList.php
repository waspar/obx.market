<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

/*
 * TODO: Добавить тип поля DATE и обработать его корректно
 * TODO: Что бы работал фильтр стоимости, необходимо подзапрос ITEMS_COST сменить на реальное поле, обновляемое на событиях
 */

namespace OBX\Market;

use OBX\Core\Tools;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;

IncludeModuleLangFile(__FILE__);

/**
 * Class OrderDBS
 * @method @static self getInstance()
 */
class OrderDBS extends DBSimple
{
	protected $_entityModuleID = 'obx.market';
	protected $_entityEventsID = 'Order';
	protected $_arTableList = array(
		'O'		=> 'obx_orders',
		'S'		=> 'obx_order_status',
		'B'		=> 'obx_basket',
		'BI'	=> 'obx_basket_items',
		'U'		=> 'b_user',
	);

	protected $_arTableFields = array(
		'ID'					=> array('O' => 'ID'),
		'DATE_CREATED'			=> array('O' => 'DATE_CREATED'),
		'TIMESTAMP_X'			=> array('O' => 'TIMESTAMP_X'),
		'USER_ID'				=> array('O' => 'USER_ID'),
		'USER_NAME'				=> array('U' => 'CONCAT(U.LAST_NAME," ",U.NAME)'),
		'STATUS_ID'				=> array('O' => 'STATUS_ID'),
		'STATUS_CODE'			=> array('S' => 'CODE'),
		'STATUS_NAME'			=> array('S' => 'NAME'),
		'STATUS_DESCRIPTION'	=> array('S' => 'DESCRIPTION'),

//		'DELIVERY_ID'			=> array('O' => 'DELIVERY_ID'),
//		'DELIVERY_COST'			=> array('O' => 'DELIVERY_COST'),
//		'PAY_ID'				=> array('O' => 'PAY_ID'),
//		'PAY_TAX_VALUE'			=> array('O' => 'PAY_TAX_VALUE'),
//		'DISCOUNT_ID'			=> array('O' => 'DISCOUNT_ID'),
//		'DISCOUNT_VALUE'		=> array('O' => 'DISCOUNT_VALUE'),

		'CURRENCY'				=> array('B' => 'CURRENCY'),
		'ITEMS_JSON' => array('BI' => <<<SQL
				concat(
					'{',
						'"items": [',
							group_concat(
								concat('{',
											'"ID":"',	BI.ID,				'",',
											'"PID":"',	BI.PRODUCT_ID,		'",',
											'"PN":"',	BI.PRODUCT_NAME,	'",',
											'"Q":"',	BI.QUANTITY,		'",',
											'"PRI":"',	BI.PRICE_ID,		'",',
											'"PRV":"',	BI.PRICE_VALUE,		'"',
									'}'
								)
							),
						' ], ',
						'"product_count": "', SUM(1) ,'", '
						'"cost": "', SUM(BI.PRICE_VALUE * BI.QUANTITY) ,'"'
					'}'
				)
SQL
				, 'REQUIRED_TABLES' => array('B')
		),
		'PRODUCT_COUNT' => array(
			'BI' => 'SUM(1)',
			'REQUIRED_TABLES' => 'B',
			'GET_LIST_FILTER' => '(
					SELECT COUNT(WBI.ID)
					FROM obx_basket_items as WBI
					WHERE WBI.BASKET_ID = B.ID
				)'
		),
		'ITEMS_COST' => array(
			'BI' => 'SUM(BI.PRICE_VALUE * BI.QUANTITY)',
			'REQUIRED_TABLES' => 'B',
			'GET_LIST_FILTER' => '(
					SELECT SUM(WBI.PRICE_VALUE * WBI.QUANTITY)
					FROM obx_basket_items as WBI
					WHERE WBI.BASKET_ID = B.ID
				)'
		),
		'PROPERTIES_JSON' => array('O' => <<<SQL
			(SELECT
				concat(
					'[',
					group_concat(
						concat('{',
									'"ID":"',	OP.ID,				'",',
									'"T":"',	OP.PROPERTY_TYPE,	'",',
									'"N":"',	OP.NAME,			'",',
									'"C":"',	OP.CODE,			'",',
									'"V":"',	(SELECT CASE OP.PROPERTY_TYPE
																WHEN 'S' THEN OPV.VALUE_S
																WHEN 'N' THEN OPV.VALUE_N
																WHEN 'T' THEN OPV.VALUE_T
																WHEN 'C' THEN OPV.VALUE_C
																WHEN 'L' THEN (
																	SELECT VALUE FROM obx_order_property_enum as OPVE
																	WHERE
																		OPV.VALUE_L = OPVE.ID
																		AND
																		OPVE.PROPERTY_ID = OPV.PROPERTY_ID
																)
																ELSE NULL
																END
															), '"',
								'}'
						)
					),
					']'
				)
			FROM
				obx_order_property as OP
			LEFT JOIN
				obx_order_property_values as OPV ON (OPV.PROPERTY_ID = OP.ID)
			WHERE
				OPV.ORDER_ID = O.ID
			GROUP BY
				OPV.ORDER_ID)
SQL
		),
	);
	protected $_arTableLeftJoin = array(
		'S'		=> 'O.STATUS_ID = S.ID',
		'B'		=> 'O.ID = B.ORDER_ID',
		'BI'	=> 'B.ID = BI.BASKET_ID',
		'U'		=> 'O.USER_ID = U.ID'
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
			'CURRENCY' => Currency::getDefault(),
			'USER_ID' => $USER->GetID(),
			'MODIFIED_BY' => $USER->GetID(),
		);

		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'DATE_CREATED' => self::FLD_T_NO_CHECK,
			'TIMESTAMP_X' => self::FLD_T_NO_CHECK,
			'USER_ID' => self::FLD_T_USER_ID | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
			'STATUS_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
			//'CURRENCY' => self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
			'DELIVERY_ID' => self::FLD_T_INT,
			'DELIVERY_COST' => self::FLD_T_FLOAT,
			'PAY_ID' => self::FLD_T_INT,
			'PAY_TAX_VALUE' => self::FLD_T_FLOAT,
			'DISCOUNT_ID' => self::FLD_T_INT,
			'DISCOUNT_VALUE' => self::FLD_T_FLOAT
		);

		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_USER_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_LIST_ERROR_1'),
				'CODE' => 1
			),
//			'REQ_FLD_CURRENCY' => array(
//				'TYPE' => 'E',
//				'TEXT' => GetMessage('OBX_ORDER_LIST_ERROR_2'),
//				'CODE' => 2
//			),
			'NOTHING_TO_DELETE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_LIST_ERROR_3'),
				'CODE' => 3
			),
			'NOTHING_TO_UPDATE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_LIST_ERROR_4'),
				'CODE' => 4
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
//			'CURRENCY' => array(
//				"NAME" => GetMessage("OBX_ORDERLIST_CURRENCY_NAME"),
//				"DESCR" => GetMessage("OBX_ORDERLIST_CURRENCY_DESCR"),
//			),
			'ITEMS' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_ITEMS_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_ITEMS_DESCR"),
			),
			'ITEMS_COST' => array(
				"NAME" => GetMessage("OBX_ORDERLIST_ITEMS_COST_NAME"),
				"DESCR" => GetMessage("OBX_ORDERLIST_ITEMS_COST_DESCR"),
			),
		);
		$this->_getEntityEvents();
	}

	protected function _onStartAdd(&$arFields) {
		$curTime = date('Y-m-d H:i:s');
		$arFields['DATE_CREATED'] = $curTime;
		return true;
	}

	protected function _onBeforeAdd(&$arFields, &$arCheckResult) {
		if( !array_key_exists('USER_ID', $arFields) || $arFields['USER_ID'] == 0 ) {
			$this->addError(GetMessage('OBX_ORDER_LIST_ERROR_1'), 1);
			return false;
		}
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
		OrderPropertyValuesDBS::getInstance()->deleteByFilter($arFilter);
		BasketItemDBS::getInstance()->deleteByFilter($arFilter);
		return true;
	}

	public function add($arFields = array()) {
		return parent::add($arFields);
	}
}

/**
 * Class OrderList
 * @method @static OrderDBS getInstance()
 */
class OrderList extends DBSimpleStatic {
	static public function add($arFields = array()) {
		return parent::add($arFields);
	}
}
OrderList::__initDBSimple(OrderDBS::getInstance());
