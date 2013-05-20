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

namespace OBX\Market;

IncludeModuleLangFile(__FILE__);

/**
 *
 */
class OrderStatusDBS extends \OBX_DBSimple {
	// TODO: Проверка на права
	const ALLOW_CHANGE_ITEMS 		=	1;
	const ALLOW_CHANGE_STATUS		=	2;
	const ALLOW_CHANGE_DELIVERY_ID	=	4;
	const ALLOW_CHANGE_PAY_ID		=	8;
	const ALLOW_CHANGE_VAT			=	16;
	const ALLOW_CHANGE_DISCOUNT		=	32;

	protected $_arTableList = array(
		'S' => 'obx_order_status'
	);
	protected $_arTableFields = array(
		'ID'					=> array('S'	=> 'ID'),
		'CODE'					=> array('S'	=> 'CODE'),
		'NAME'					=> array('S'	=> 'NAME'),
		'DESCRIPTION'			=> array('S'	=> 'DESCRIPTION'),
		'CHANGE_HANDLER_CLASS'	=> array('S'	=> 'CHANGE_HANDLER_CLASS'),
		'CHANGE_HANDLER_METHOD'	=> array('S'	=> 'CHANGE_HANDLER_METHOD'),
		'COLOR'					=> array('S'	=> 'COLOR'),
		'SORT'					=> array('S'	=> 'SORT'),
		'ACTIVE'				=> array('S'	=> 'ACTIVE'),
		'PERMISSION'			=> array('S'	=> 'PERMISSION'),
		'IS_SYS'				=> array('S'	=> 'IS_SYS')
	);
	protected $_mainTable = 'S';
	protected $_mainTablePrimaryKey = 'ID';
	protected $_mainTableAutoIncrement = 'ID';
	protected $_arTableUnique = array(
		"udx_obx_order_status" => array("CODE")
	);

	protected $_arFieldsEditInAdmin = array(
		'CODE', 'NAME', 'COLOR', 'DESCRIPTION',
	);

	protected $_arSelectDefault = array(
		'ID', 'CODE', 'NAME', 'DESCRIPTION', 'COLOR', 'SORT', 'ACTIVE', 'IS_SYS'
	);
	protected $_arSortDefault = array('SORT' => 'ASC', 'ID' => 'ASC');
	protected $_arTableFieldsDefault = array(
		'SORT' => '100',
		'ACTIVE' => 'Y',
		'IS_SYS' => 'N',
	);
	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'CODE' => self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'NAME' => self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'DESCRIPTION' => self::FLD_T_STRING,
			'CHANGE_HANDLER_CLASS' => self::FLD_T_CODE,
			'CHANGE_HANDLER_METHOD' => self::FLD_T_CODE,
			'COLOR' => self::FLD_T_NO_CHECK | self::FLD_CUSTOM_CK | self::FLD_BRK_INCORR,
			'SORT' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_DEFAULT,
			'ACTIVE' => self::FLD_T_BCHAR | self::FLD_NOT_NULL | self::FLD_DEFAULT,
			'IS_SYS' => self::FLD_T_BCHAR | self::FLD_NOT_NULL | self::FLD_DEFAULT
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
	}
	function __check_COLOR(&$fieldValue, &$arCheckData) {
		$fieldValue = trim($fieldValue, ' #');
		$lenValue = strlen($fieldValue);
		if( $lenValue > 0 && !preg_match('~^[0-9a-fA-F]{6}$~', $fieldValue) ) {
			$this->addError(GetMessage('OBX_ORDER_STATUS_ERROR_5'), 5);
			return false;
		}
		return true;
	}

	protected function _onStartAdd(&$arFields) {
		if( array_key_exists('IS_SYS', $arFields) && !array_key_exists(OBX_MAGIC_WORD, $arFields) ) {
			unset($arFields['IS_SYS']);
		}
		return true;
	}
	protected function _onStartUpdate(&$arFields) {
		if( array_key_exists('IS_SYS', $arFields) && !array_key_exists(OBX_MAGIC_WORD, $arFields) ) {
			unset($arFields['IS_SYS']);
		}
		return true;
	}
	protected function _onBeforeExecUpdate(&$arFields, &$arCheckResult) {
		$arProp = &$arCheckResult['__EXIST_ROW'];
		if (array_key_exists('CODE', $arFields)) {
			if ($arProp['IS_SYS'] == 'Y' && !array_key_exists('IS_SYS', $arFields) && $arFields['IS_SYS']!='N' ) {
				if ($arFields['CODE'] != $arProp['CODE']) {
					$arFields['CODE'] = $arProp['CODE'];
					$this->addError(GetMessage('OBX_ORDER_STATUS_ERROR_8'), 1);
					return false;
				}
			}
		}
		return true;
	}
	protected function _onBeforeDelete(&$arItem) {
		if ($arItem['IS_SYS'] == 'Y') {
			$this->addError(GetMessage('OBX_ORDER_STATUS_ERROR_9'), 1);
			return false;
		}
		return true;
	}
}

class OrderStatus extends \OBX_DBSimpleStatic {}
OrderStatus::__initDBSimple(OrderStatusDBS::getInstance());
