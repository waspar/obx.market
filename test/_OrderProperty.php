<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

OBX_Market_TestCase::includeLang(__FILE__);

require_once dirname(__FILE__).'/_Order.php';

class OBX_Test_Lib_OrderProperty extends OBX_Test_Lib_Order
{
	static protected  $_arPropertyList = array();
	static public function setUpBeforeClass() {
		self::$_arPropertyList = array(
			'TT_NUMBER' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_NUMBER_NAME'),
				'DESCRIPTION' => GetMessage('OBX_MARKET_TT_NUMBER_DESCR'),
				'PROPERTY_TYPE' => 'N'
			),
			'TT_STRING' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_STRING_NAME'),
				'DESCRIPTION' => GetMessage('OBX_MARKET_TT_STRING_DESCR'),
				'PROPERTY_TYPE' => 'S'
			),
			'TT_TEXTAREA' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_TEXTAREA_NAME'),
				'DESCRIPTION' => GetMessage('OBX_MARKET_TT_TEXTAREA_DESCR'),
				'PROPERTY_TYPE' => 'T'
			),
			'TT_CHECKBOX' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_CHECKBOX_NAME'),
				'PROPERTY_TYPE' => 'C',
				'DESCRIPTION' => GetMessage('OBX_MARKET_TT_CHECKBOX_DESCR')
			),
			'TT_LIST' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_LIST_NAME'),
				'DESCRIPTION' => GetMessage('OBX_MARKET_TT_LIST_DESCR'),
				'PROPERTY_TYPE' => 'C',
				'VALUES' => array(
					1 => GetMessage('OBX_MARKET_TT_LIST_VALUE_1'),
					2 => GetMessage('OBX_MARKET_TT_LIST_VALUE_2'),
					3 => GetMessage('OBX_MARKET_TT_LIST_VALUE_3'),
					4 => GetMessage('OBX_MARKET_TT_LIST_VALUE_4')
				)
			),
			'TT_SYSTEM_NUMBER' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_SYSTEM_NUMBER_NAME'),
				'DESCRIPTION' => GetMessage('OBX_MARKET_TT_SYSTEM_NUMBER_DESCR'),
				'PROPERTY_TYPE' => 'N',
				'IS_SYS' => 'Y'
			),
		);
		foreach(self::$_arPropertyList as $propCode => &$arProperty) {
			$arProperty['CODE'] = $propCode;
			OBX_OrderProperty::deleteByFilter(array('CODE' => array(
				'TT_NUMBER', 'TT_STRING', 'TT_TEXTAREA', 'TT_CHECKBOX', 'TT_LIST', 'TT_SYSTEM_NUMBER', 'TT_SYSTEM_STRING'
			)));
		}
	}

	public function _addProperty() {
		foreach(self::$_arPropertyList as $propCode => &$arFields) {
			$propID = OBX_OrderProperty::add($arFields);
			if(!$propID) {
				$arError = OBX_OrderProperty::popLastError('ARRAY');
				$this->assertGreaterThan(0, $propID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
			}
			$arProperty = OBX_OrderProperty::getByID($propID, array(
				'ID', 'CODE', 'NAME', 'PROPERTY_TYPE',
				'DESCRIPTION', 'IS_SYS'));
			$this->assertArrayHasKey('ID', $arProperty);
			$this->assertArrayHasKey('CODE', $arProperty);
			$this->assertArrayHasKey('NAME', $arProperty);
			$this->assertArrayHasKey('DESCRIPTION', $arProperty);
			$this->assertArrayHasKey('PROPERTY_TYPE', $arProperty);
			$this->assertArrayHasKey('IS_SYS', $arProperty);
			$this->assertEquals($arFields['CODE'], $arProperty['CODE']);
			$this->assertEquals($arFields['NAME'], $arProperty['NAME']);
			$this->assertEquals($arFields['DESCRIPTION'], $arProperty['DESCRIPTION']);
			$this->assertEquals($arFields['PROPERTY_TYPE'], $arProperty['PROPERTY_TYPE']);
			if($propCode == 'TT_SYSTEM_NUMBER' || $propCode == 'TT_SYSTEM_STRING') {
				// Не смотря на то, что IS_SYS выставлен в 'Y', без OBX_MAGIC_WORD не сработает
				$this->assertEquals('Y', $arFields['IS_SYS']);
			}
			$this->assertEquals('N', $arProperty['IS_SYS']);
			$arFields = $arProperty;
		}
	}

	public function _deleteProperty() {
		foreach(self::$_arPropertyList as $propCode => &$arFields) {
			$bSuccess = OBX_OrderProperty::delete($arFields['ID']);
			if(!$bSuccess) {
				$arError = OBX_OrderProperty::popLastError('ARRAY');
				$this->assertTrue($bSuccess, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
			}
		}
	}

	public function & _getPropertyList() {
		return self::$_arPropertyList;
	}
}