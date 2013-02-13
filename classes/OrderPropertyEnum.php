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
IncludeModuleLangFile(__FILE__);

/**
 *
 */
class OBX_OrderPropertyEnumDBS extends OBX_DBSimple {
	protected $_arTableList = array(
		'OPE' => 'obx_order_property_enum',
		'OP' => 'obx_order_property'
	);
	protected $_mainTable = 'OPE';
	protected $_arTableFields = array(
		'ID'				=> array('OPE'	=> 'ID'),
		'CODE'				=> array('OPE'	=> 'CODE'),
		'VALUE'				=> array('OPE'	=> 'VALUE'),
		'SORT'				=> array('OPE'	=> 'SORT'),
		//'IS_DEFAULT'		=> array('OPE'	=> 'IS_DEFAULT'),
		'PROPERTY_ID'		=> array('OPE'	=> 'PROPERTY_ID'),
		'PROPERTY_CODE'		=> array('OP'	=> 'CODE'),
		'PROPERTY_NAME'		=> array('OP'	=> 'NAME'),
	);
	protected $_arTableUnique = array(
		'udx_obx_order_property_enum' => array('CODE', 'PROPERTY_ID')
	);
	protected $_arTableLinks = array(
		0 => array(
			array('OP' => 'ID'),
			array('OPE' => 'PROPERTY_ID')
		)
	);
	protected $_arTableFieldsDefault = array(
		'SORT' => '100'
	);
	protected $_arSortDefault = array('SORT' => 'ASC', 'ID' => 'ASC');
	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'CODE' => self::FLD_T_IDENT | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'PROPERTY_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_REQUIRED | self::FLD_CUSTOM_CK,
			'VALUE' => self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'SORT' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_DEFAULT
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_CODE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_ENUM_ERROR_1'),
				'CODE' => 1
			),
			'REQ_FLD_PROPERTY_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_ENUM_ERROR_2'),
				'CODE' => 2
			),
			'REQ_FLD_VALUE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_ENUM_ERROR_3'),
				'CODE' => 3
			),
			'DUP_ADD_udx_obx_order_property_enum' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_ENUM_ERROR_4'),
				'CODE' => 4
			),
			'DUP_UPD_udx_obx_order_property_enum' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_ENUM_ERROR_5'),
				'CODE' => 5
			)
		);
	}

	public function __check_PROPERTY_ID(&$fieldValue, &$arCheckData) {
		$arProp = OBX_OrderPropertyDBS::getInstance()->getByID($fieldValue);
		if( empty($arProp) || !is_array($arProp) ) {
			return false;
		}
		$arCheckData = $arProp;
		return true;
	}
}
class OBX_OrderPropertyEnum extends OBX_DBSimpleStatic {}
OBX_OrderPropertyEnum::__initDBSimple(OBX_OrderPropertyEnumDBS::getInstance());
?>