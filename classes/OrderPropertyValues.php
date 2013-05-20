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
class OrderPropertyValuesDBS extends \OBX_DBSimple {
	protected $_arTableList = array(
		'O' => 'obx_orders',
		'OP' => 'obx_order_property',
		'OPV' => 'obx_order_property_values',
	);
	protected $_mainTable = 'OPV';
	protected $_arTableFields = array(
		'ID' => array('OPV' => 'ID'),
		'ORDER_ID'			=> array('O'	=> 'ID'),
		'ORDER_USER_ID'		=> array('O'	=> 'USER_ID'),
		'PROPERTY_ID'		=> array('OP'	=> 'ID'),
		'PROPERTY_CODE'		=> array('OP'	=> 'CODE'),
		'PROPERTY_NAME'		=> array('OP'	=> 'NAME'),
		'PROPERTY_TYPE'		=> array('OP'	=> 'PROPERTY_TYPE'),
		'PROPERTY_IS_SYS'	=> array('OP'	=> 'IS_SYS'),
		'PROPERTY_SORT'		=> array('OP'	=> 'SORT'),
		'VALUE'				=> array('OP'	=> <<<SQL
								(SELECT CASE OP.PROPERTY_TYPE
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
								)
SQL
							),
		'VALUE_S'			=> array('OPV'	=> 'VALUE_S'),
		'VALUE_N'			=> array('OPV'	=> 'VALUE_N'),
		'VALUE_T'			=> array('OPV'	=> 'VALUE_T'),
		'VALUE_L'			=> array('OPV'	=> 'VALUE_L'),
		'VALUE_ENUM_ID'		=> array('OPV'	=> 'VALUE_L'),
		'VALUE_ENUM_CODE'		=> array('OPV'	=>
								'(SELECT CODE
									FROM obx_order_property_enum as OPVE
									WHERE OPV.VALUE_L = OPVE.ID
								)'
							),
		'VALUE_C'			=> array('OPV'	=> 'VALUE_C'),
	);
	protected $_arTableLeftJoin = array(
		'OP' => 'true',
		'OPV' => 'OP.ID = OPV.PROPERTY_ID AND O.ID = OPV.ORDER_ID',
	);
	protected $_arTableUnique = array(
		'udx_obx_order_property_values' => array('ORDER_ID', 'PROPERTY_ID')
	);
	protected $_arSelectDefault = array(
		'ID',
		'ORDER_ID',
		'PROPERTY_ID',
		'VALUE',
		'VALUE_ENUM_ID'
	);

	function __construct() {
		$this->_arTableLinks = array(
			array(
				array('O' => 'ID'),
				array('OPV' => 'ORDER_ID'),
			),
			array(
				array('OP' => 'ID'),
				array('OPV' => 'PROPERTY_ID'),
			)
		);
		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'ORDER_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_REQUIRED | self::FLD_CUSTOM_CK,
			'PROPERTY_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_REQUIRED | self::FLD_CUSTOM_CK,
			'VALUE' => self::FLD_T_NO_CHECK,
			'VALUE_S' => self::FLD_T_STRING,
			'VALUE_N' => self::FLD_T_FLOAT,
			'VALUE_T' => self::FLD_T_STRING,
			'VALUE_L' => self::FLD_T_INT,
			'VALUE_C' => self::FLD_T_BCHAR,
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_ORDER_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_VALUES_ERROR_1'),
				'CODE' => 1
			),
			'REQ_FLD_PROPERTY_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_VALUES_ERROR_2'),
				'CODE' => 2
			),
			'DUP_ADD_udx_obx_order_property_values' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_VALUES_ERROR_4'),
				'CODE' => 4
			),
			'DUP_UPD_udx_obx_order_property_values' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_ORDER_PROP_VALUES_ERROR_5'),
				'CODE' => 5
			)
		);
	}

	public function __check_ORDER_ID(&$fieldValue, &$arCheckData) {
		$arOrder = OrderDBS::getInstance()->getByID($fieldValue);
		if (empty($arOrder) || !is_array($arOrder)) {
			return false;
		}
		$arCheckData = $arOrder;
		return true;
	}

	public function __check_PROPERTY_ID(&$fieldValue, &$arCheckData) {
		$arProp = OrderPropertyDBS::getInstance()->getByID($fieldValue);
		if (empty($arProp) || !is_array($arProp)) {
			return false;
		}
		$arCheckData = $arProp;
		return true;
	}

	protected function _onBeforeAdd(&$arFields, &$arCheckResult) {
		return $this->_fixAddUpdateFields($arFields, $arCheckResult);
	}

	protected function _onBeforeUpdate(&$arFields, &$arCheckResult) {
		return $this->_fixAddUpdateFields($arFields, $arCheckResult);
	}

	protected function _fixAddUpdateFields(&$arFields, &$arCheckResult) {
		if (
			!$arCheckResult['ORDER_ID']['IS_CORRECT']
			||
			!$arCheckResult['PROPERTY_ID']['IS_CORRECT']
		) {
			return false;
		}
		switch ($arCheckResult['PROPERTY_ID']['CHECK_DATA']['PROPERTY_TYPE']) {
			case 'S':
				if (!isset($arFields['VALUE_S'])) {
					if (!isset($arFields['VALUE'])) {
						$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_3'), 3);
						return false;
					}
					$arFields['VALUE_S'] = $arFields['VALUE'];
				}
				$arFields['VALUE_S'] = htmlspecialcharsEx($arFields['VALUE_S']);
				unset($arFields['VALUE']);
				unset($arFields['VALUE_N']);
				unset($arFields['VALUE_T']);
				unset($arFields['VALUE_L']);
				unset($arFields['VALUE_C']);
				break;
			case 'N':
				if (!isset($arFields['VALUE_N'])) {
					if (!isset($arFields['VALUE'])) {
						$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_3'), 3);
						return false;
					}
					if (empty($arFields['VALUE'])) {
						$arFields['VALUE'] = 0;
					}
					$arFields['VALUE_N'] = $arFields['VALUE'];
				}
				$arFields['VALUE_N'] = floatval($arFields['VALUE_N']);
				unset($arFields['VALUE']);
				unset($arFields['VALUE_S']);
				unset($arFields['VALUE_T']);
				unset($arFields['VALUE_L']);
				unset($arFields['VALUE_C']);
				break;
			case 'T':
				if (!isset($arFields['VALUE_T'])) {
					if (!isset($arFields['VALUE'])) {
						$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_3'), 3);
						return false;
					}
					$arFields['VALUE_T'] = $arFields['VALUE'];
				}
				$arFields['VALUE_T'] = htmlspecialcharsEx($arFields['VALUE_T']);
				unset($arFields['VALUE']);
				unset($arFields['VALUE_S']);
				unset($arFields['VALUE_N']);
				unset($arFields['VALUE_L']);
				unset($arFields['VALUE_C']);
				break;
			case 'L':
				if (!isset($arFields['VALUE_L'])) {
					if (!isset($arFields['VALUE'])) {
						$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_3'), 3);
						return false;
					}
					$arFields['VALUE_L'] = $arFields['VALUE'];
				}
				$arFields['VALUE_L'] = trim($arFields['VALUE_L']);
				unset($arFields['VALUE']);
				unset($arFields['VALUE_S']);
				unset($arFields['VALUE_N']);
				unset($arFields['VALUE_T']);
				unset($arFields['VALUE_C']);

				$arPropEnums = OrderPropertyEnumDBS::getInstance()->getListArray(null, array(
					'PROPERTY_ID' => $arCheckResult['PROPERTY_ID']['CHECK_DATA']['ID']
				));
				$bFound = false;
				foreach ($arPropEnums as $arEnum) {
					// pr0n1x [2013-01-20]: Сначала проверяем на CODE,
					// именно его вероятнее всего будут выставлять в качестве значения.
					// К тому же к коде enum-значения допускается испольвзатние чисел.
					// Потому актуальнее сначала проверять на код
					if ($arEnum['CODE'] == $arFields['VALUE_L']) {
						$arFields['VALUE_L'] = $arEnum['ID'];
						$bFound = true;
						break;
					}
					elseif ($arEnum['ID'] == $arFields['VALUE_L']) {
						$arFields['VALUE_L'] = intval($arFields['VALUE_L']);
						$bFound = true;
						break;
					}
				}
				if (!$bFound) {
					$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_6'), 6);
					return false;
				}
				break;
			case 'C':
				if (!isset($arFields['VALUE_C']) || empty($arFields['VALUE_C'])) {
					if (!isset($arFields['VALUE']) || empty($arFields['VALUE'])) {
						$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_3'), 3);
						return false;
					}
					$arFields['VALUE_C'] = $arFields['VALUE'];
				}
				$arFields['VALUE_C'] = substr($arFields['VALUE_C'], 0, 1);
				unset($arFields['VALUE']);
				unset($arFields['VALUE_S']);
				unset($arFields['VALUE_N']);
				unset($arFields['VALUE_T']);
				unset($arFields['VALUE_L']);
				break;

			default:
				$this->addError(GetMessage('OBX_ORDER_PROP_VALUES_ERROR_7'), 7);
				return false;
				break;
		}
		return true;
	}
}

class OrderPropertyValues extends \OBX_DBSimpleStatic {}
OrderPropertyValues::__initDBSimple(OrderPropertyValuesDBS::getInstance());
