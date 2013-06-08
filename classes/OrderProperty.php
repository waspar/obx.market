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
use OBX\Core\CMessagePool;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\CMessagePoolDecorator;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;
use OBX\Core\DBSResult;

IncludeModuleLangFile(__FILE__);
class OrderPropertyDBS extends DBSimple {
	protected $_arTableList = array(
		'OP' => 'obx_order_property'
	);

	protected $_arTableFields = array(
		'ID' => array('OP' => 'ID'),
		'CODE' => array('OP' => 'CODE'),
		'NAME' => array('OP' => 'NAME'),
		'DESCRIPTION' => array('OP' => 'DESCRIPTION'),
		'PROPERTY_TYPE' => array('OP' => 'PROPERTY_TYPE'),
		'SORT' => array('OP' => 'SORT'),
		'ACTIVE' => array('OP' => 'ACTIVE'),
		'IS_SYS' => array('OP' => 'IS_SYS'),
		'ACCESS' => array('OP' => 'ACCESS'),
	);

	protected $_mainTable = 'OP';
	protected $_mainTablePrimaryKey = 'ID';
	protected $_mainTableAutoIncrement = 'ID';

	protected $_arTableFieldsDefault = array(
		'SORT' => '100',
		'ACTIVE' => 'Y',
		'IS_SYS' => 'N',
		'ACCESS' => 'W',
	);
	protected $_arSelectDefault = array(
		'ID',
		'CODE',
		'NAME',
		'DESCRIPTION',
		'PROPERTY_TYPE',
		'SORT'
	);

	protected $_arSortDefault = array('SORT' => 'ASC', 'ID' => 'ASC');

	protected $_arTableUnique = array(
		'udx_obx_order_property' => array('CODE')
	);

	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'CODE' => self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'NAME' => self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'DESCRIPTION' => self::FLD_T_STRING,
			'PROPERTY_TYPE' => self::FLD_T_CHAR | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'SORT' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_DEFAULT,
			'ACTIVE' => self::FLD_T_BCHAR | self::FLD_NOT_NULL | self::FLD_DEFAULT,
			'IS_SYS' => self::FLD_T_BCHAR | self::FLD_NOT_NULL | self::FLD_DEFAULT,
			'ACCESS' => self::FLD_T_CHAR | self::FLD_NOT_NULL | self::FLD_DEFAULT,
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_NAME' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_ORDER_PROP_ERROR_1'),
				'CODE' => 1
			),
			'REQ_FLD_CODE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_ORDER_PROP_ERROR_2'),
				'CODE' => 2
			),
			'REQ_FLD_PROPERTY_TYPE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_ORDER_PROP_ERROR_3'),
				'CODE' => 3
			),
			'DUP_ADD_udx_obx_order_property' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_ORDER_PROP_ERROR_4'),
				'CODE' => 4
			),
			'DUP_UPD_udx_obx_order_property' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_ORDER_PROP_ERROR_5'),
				'CODE' => 5
			)
		);
		$this->_arFieldsDescription = array(
			'CODE' => array(
				'NAME' => GetMessage('OBX_MARKET_ORDER_PROP_CODE_NAME'),
				'DESCR' => GetMessage('OBX_MARKET_ORDER_PROP_CODE_DESCR')
			),
			'PROPERTY_TYPE' => array(
				'NAME' => GetMessage('OBX_MARKET_ORDER_PROP_PROPERTY_TYPE_NAME'),
				'DESCR' => GetMessage('OBX_MARKET_ORDER_PROP_PROPERTY_TYPE_DESCR')
			)
		);
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
			if( $arProp['IS_SYS'] == 'Y'
				&& !array_key_exists('IS_SYS', $arFields)
				&& $arFields['IS_SYS']!='N'
				&& !$arCheckResult['__MAGIC_WORD']
			) {
				if ($arFields['CODE'] != $arProp['CODE']) {
					$arFields['CODE'] = $arProp['CODE'];
					$this->addError(GetMessage('OBX_MARKET_ORDER_PROP_ERROR_5'), 5);
					return false;
				}
			}
		}
		return true;
	}

	protected function _onBeforeDelete(&$arFields) {
		if ($arFields['IS_SYS'] == 'Y') {
			$this->addError(GetMessage('OBX_MARKET_ORDER_PROP_ERROR_6'), 6);
			return false;
		}
		OrderPropertyValuesDBS::getInstance()->deleteByFilter(array(
			'PROPERTY_ID' => $arFields['ID']
		));
		if ($arFields['PROPERTY_TYPE'] == 'L') {
			OrderPropertyEnumDBS::getInstance()->deleteByFilter(array(
				'PROPERTY_ID' => $arFields['ID']
			));
		}
		return true;
	}


	public function registerModuleDependencies() {

	}

	public function unRegisterModuleDependencies() {

	}
}

/**
 * Class OrderProperty
 * @package OBX\Market
 * @method @static OrderProperty getInstance
 */
class OrderProperty extends DBSimpleStatic {
	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}

	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}

OrderProperty::__initDBSimple(OrderPropertyDBS::getInstance());
