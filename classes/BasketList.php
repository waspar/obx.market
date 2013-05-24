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

class BasketDBS extends \OBX_DBSimple
{
	protected $_mainTable = 'B';
	protected $_arTableList = array(
		'B'		=> 'obx_basket',
		'BI'	=> 'obx_basket_items'
	);
	protected $_arTableFields = array(
		'ID'				=> array('B' => 'ID'),
		'ORDER_ID'			=> array('B' => 'ORDER_ID'),
		'USER_ID'			=> array('B' => 'USER_ID'),
		'HASH_STRING'		=> array('B' => 'HASH_STRING'),
		'CURRENCY'			=> array('B' => 'CURRENCY'),
		'ITEMS_JSON' => array('BI' => <<<SQL
				concat(
					'{ ',
						'"items": [',
							group_concat(
								concat('{ ',
											'"ID": "',				BI.ID,				'", ',
											'"PRODUCT_ID": "',		BI.PRODUCT_ID,		'", ',
											'"PRODUCT_NAME": "',	BI.PRODUCT_NAME,	'", ',
											'"QUANTITY": "',		BI.QUANTITY,		'", ',
											'"PRICE_VALUE": "',		BI.PRICE_VALUE,		'"',
									'" }'
								)
							),
						' ], ',
						'"product_count": "', SUM(1) ,'", '
						'"items_count": "', SUM(BI.QUANTITY) ,'", '
						'"cost": "', SUM(BI.PRICE_VALUE * BI.QUANTITY) ,'"'
					' }'
				)
SQL
		),
		'ITEMS_COST' => array('BI' => 'SUM(BI.PRICE_VALUE * BI.QUANTITY)'),
	);
	protected $_arTableLeftJoin = array(
		'BI' => 'B.ID = BI.BASKET_ID'
	);
	protected $_arGroupByFields = array(
		'B' => 'ID'
	);

	protected $_arSelectDefault = array(
		'ID', 'ORDER_ID', 'USER_ID', 'HASH_STRING', 'CURRENCY'
	);

	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_NOT_ZERO,
			'ORDER_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_NOT_ZERO | self::FLD_CUSTOM_CK,
			'USER_ID' => self::FLD_T_USER_ID | self::FLD_NOT_NULL | self::FLD_NOT_ZERO,
			'HASH_STRING' => self::FLD_T_IDENT | self::FLD_CUSTOM_CK,
			'CURRENCY' => self::FLD_T_NO_CHECK | self::FLD_CUSTOM_CK | self::FLD_REQUIRED | self::FLD_BRK_INCORR
		);
	}

	public function __check_HASH_STRING(&$value, &$arCheckData = null) {
		if(
			! is_string($value)
			||
			! preg_match('~[a-f0-9]{32}~', $value)
		) {
			if($arCheckData !== null) {
				$this->addError(GetMessage('OBX_VISITORS_ERROR_WRONG_COOKIE_ID', 7));
			}
			return false;
		}
		return true;
	}

	public function __check_ORDER_ID(&$value, &$arCheckData) {
		if($value !== null) {
			$rsOrder = OrderDBS::getInstance()->getByID($value, null, true);
			if( ! ($arOrder = $rsOrder->Fetch()) ) {
				if($arCheckData !== null) {
					$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_7'), 7);
				}
			}
			$arCheckData = $arOrder;
		}
		return true;
	}

	public function __check_CURRENCY(&$value, &$arCheckData) {
		$arCurrency = Currency::getByID($value);
		if( empty($arCurrency) ) {
			return false;
		}
		$arCheckData = $arCurrency;
		return true;
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

	protected function _onBeforeAdd(&$arFields, &$arCheckData) {
		if( !empty($arFields['ORDER_ID']) ) {
			$arFields['USER_ID'] = null;
			$arFields['HASH_STRING'] = null;
			$rsExistsBasket = $this->getList(null, $arFields);
			if($arExistsBasket = $rsExistsBasket->Fetch()) {
				$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_1'), 1);
				return false;
			}
		}
		elseif( !empty($arFields['USER_ID']) ) {
			$arFields['ORDER_ID'] = null;
			$arFields['HASH_STRING'] = null;
			$rsExistsBasket = $this->getList(null, $arFields);
			if($arExistsBasket = $rsExistsBasket->Fetch()) {
				$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_2'), 2);
				return false;
			}
		}
		elseif( !empty($arFields['HASH_STRING']) ) {
			$arFields['USER_ID'] = null;
			$arFields['ORDER_ID'] = null;
			$rsExistsBasket = $this->getList(null, array('HASH_STRING' => $arFields['HASH_STRING']));
			if($arExistsBasket = $rsExistsBasket->Fetch()) {
				$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_3', array('#HASH_STRING#' => $arFields['HASH_STRING'])), 3);
				return false;
			}
		}
		return true;
	}

	protected function _onBeforeUpdate(&$arFields, &$arCheckData) {
		if( array_key_exists('ORDER_ID', $arFields) ) {
			if( array_key_exists('USER_ID', $arFields) ) {
				if($arCheckData['ORDER_ID']['CHECK_DATA']['USER_ID'] != $arFields['USER_ID']) {
					$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_8'), 8);
					return false;
				}
			}
			$rsExistsBasket = $this->getList(null, array('ORDER_ID' => $arFields['ORDER_ID'], '!ID' => $arFields['ID']));
			if($arExistsBasket = $rsExistsBasket->Fetch()) {
				if( array_key_exists('USER_ID', $arFields) ) {
					$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_4'), 4);
				}
				else {
					$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_5'), 5);
				}
				return false;
			}
			$arFields['HASH_STRING'] = null;
			$arFields['USER_ID'] = null;
			return true;
		}
		if( array_key_exists('USER_ID', $arFields) ) {
			$rsExistsBasket = $this->getList(null, array('USER_ID' => $arFields['USER_ID'], '!ID' => $arFields['ID']));
			if($arExistsBasket = $rsExistsBasket->Fetch()) {
				$this->addError(GetMessage('OBX_BASKET_LIST_ERROR_6'), 6);
				return false;
			}
			$arFields['HASH_STRING'] = null;
			$arFields['ORDER_ID'] = null;
			return true;
		}
		return true;
	}

	protected function _onBeforeExecUpdate(&$arFields, &$arCheckResult) {
		return true;
	}
}
class BasketList extends \OBX_DBSimpleStatic {
	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}

	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}

BasketList::__initDBSimple(BasketDBS::getInstance());