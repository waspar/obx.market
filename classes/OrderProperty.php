<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/
/**
 *
 */
IncludeModuleLangFile(__FILE__);
class OBX_OrderPropertyDBS extends OBX_DBSimple {
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
					$this->addError(GetMessage('OBX_PROPERTY_ERROR_1'), 1);
					return false;
				}
			}
		}
		return true;
	}

	protected function _onBeforeDelete(&$arItem) {
		if ($arItem['IS_SYS'] == 'Y') {
			$this->addError(GetMessage('OBX_PROPERTY_ERROR_1'), 1);
			return false;
		}
		OBX_OrderPropertyValuesDBS::getInstance()->deleteByFilter(array(
			'PROPERTY_ID' => $arItem['ID']
		));
		if ($arItem['PROPERTY_TYPE'] == 'L') {
			OBX_OrderPropertyEnumDBS::getInstance()->deleteByFilter(array(
				'PROPERTY_ID' => $arItem['ID']
			));
		}
		return true;
	}


	public function registerModuleDependencies() {

	}

	public function unRegisterModuleDependencies() {

	}
}

class OBX_OrderProperty extends OBX_DBSimpleStatic {
	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}

	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}

OBX_OrderProperty::__initDBSimple(OBX_OrderPropertyDBS::getInstance());
?>