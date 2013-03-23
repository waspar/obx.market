<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ***************************************/

OBX_Market_TestCase::includeLang(__FILE__);

final class OBX_Test_OrderProperty extends OBX_Market_TestCase
{
	static private $_arPropertyList = array();

	public function testPrepareTests() {
		self::$_arPropertyList = array(
			'TT_NUMBER' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_NUMBER_NAME'),
				'PROPERTY_TYPE' => 'N'
			),
			'TT_STRING' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_STRING_NAME'),
				'PROPERTY_TYPE' => 'S'
			),
			'TT_TEXTAREA' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_TEXTAREA_NAME'),
				'PROPERTY_TYPE' => 'T'
			),
			'TT_CHECKBOX' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_CHECKBOX_NAME'),
				'PROPERTY_TYPE' => 'C'
			),
			'TT_LIST' => array(
				'NAME' => GetMessage('OBX_MARKET_TT_LIST_NAME'),
				'PROPERTY_TYPE' => 'C',
				'VALUES' => array(
					1 => GetMessage('OBX_MARKET_TT_LIST_VALUE_1'),
					2 => GetMessage('OBX_MARKET_TT_LIST_VALUE_2'),
					3 => GetMessage('OBX_MARKET_TT_LIST_VALUE_3'),
					4 => GetMessage('OBX_MARKET_TT_LIST_VALUE_4')
				)
			),
		);
	}

	public function testAddProperty() {
		foreach(self::$_arPropertyList as $propCode => $arFields) {
			$arFields['CODE'] = $propCode;
			$propID = OBX_OrderProperty::add($arFields);
			if(!$propID) {
				$arError = OBX_OrderProperty::popLastError('ARRAY');
				$this->assertGreaterThan(0, $propID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
			}
		}
	}

	public function testUpdateProperty() {
		
	}

	public function testDeleteProperty() {
		
	}
}
