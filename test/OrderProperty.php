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

	public function testAddProperty() {
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

	public function testUpdateProperty() {
		foreach(self::$_arPropertyList as $propCode => &$arFields) {
			$bSuccess = OBX_OrderProperty::update(array(
				'ID' => $arFields['ID'],
				'DESCRIPTION' => $arFields['DESCRIPTION'].' - UPDATED'
			));
			if(!$bSuccess) {
				$arError = OBX_OrderProperty::popLastError('ARRAY');
				$this->assertTrue($bSuccess, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
			}
			$arProperty = OBX_OrderProperty::getByID($arFields['ID'], array(
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
			$this->assertEquals($arFields['PROPERTY_TYPE'], $arProperty['PROPERTY_TYPE']);
			$this->assertEquals('N', $arProperty['IS_SYS']);

			$this->assertNotEquals($arFields['DESCRIPTION'], $arProperty['DESCRIPTION']);
			$this->assertEquals($arFields['DESCRIPTION'].' - UPDATED', $arProperty['DESCRIPTION']);
			$arFields = $arProperty;
		}
	}

	public function testSetSystemBit() {
		$bSuccess = OBX_OrderProperty::update(array(
			'ID' => self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'],
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => I_KNOW_WHAT_I_DO
		));
		if(!$bSuccess) {
			$arError = OBX_OrderProperty::popLastError('ARRAY');
			$this->assertTrue($bSuccess, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
		}
		$arProperty = OBX_OrderProperty::getByID(self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'], array(
			'ID', 'CODE', 'NAME', 'PROPERTY_TYPE',
			'DESCRIPTION', 'IS_SYS'));
		$this->assertArrayHasKey('IS_SYS', $arProperty);
		$this->assertEquals('Y', $arProperty['IS_SYS'], 'Error: failed to set system-bit to property');
		self::$_arPropertyList['TT_SYSTEM_NUMBER'] = $arProperty;
	}

	public function testAddNewSystemProperty() {
		$propID = OBX_OrderProperty::add(array(
			'CODE' => 'TT_SYSTEM_STRING',
			'NAME' => GetMessage('OBX_MARKET_TT_SYSTEM_STRING_NAME'),
			'DESCRIPTION' => GetMessage('OBX_MARKET_TT_SYSTEM_STRING_DESCR'),
			'PROPERTY_TYPE' => 'S',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => I_KNOW_WHAT_I_DO
		));
		if($propID < 1) {
			$arError = OBX_OrderProperty::popLastError('ARRAY');
			$this->assertGreaterThan(0, $propID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
		}
		$arProperty = OBX_OrderProperty::getByID($propID, array(
			'ID', 'CODE', 'NAME', 'PROPERTY_TYPE',
			'DESCRIPTION', 'IS_SYS'));
		$this->assertArrayHasKey('IS_SYS', $arProperty);
		$this->assertEquals('Y', $arProperty['IS_SYS'], 'Error: failed to add system property');
		self::$_arPropertyList['TT_SYSTEM_STRING'] = $arProperty;
	}

	public function testTryingToUpdateCodeOfSystemProperty() {
		$bSuccess = OBX_OrderProperty::update(array(
			'ID' => self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'],
			'CODE' => '__CODE__'
		));
		$this->assertFalse($bSuccess);
		$arError = OBX_OrderProperty::popLastError('ARRAY');
		$this->assertEquals(5, $arError['CODE'],
			'Error: expects error-code 5, but returned code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');

		$bSuccess = OBX_OrderProperty::update(array(
			'ID' => self::$_arPropertyList['TT_SYSTEM_STRING']['ID'],
			'CODE' => '__CODE__'
		));
		$this->assertFalse($bSuccess);
		$arError = OBX_OrderProperty::popLastError('ARRAY');
		$this->assertEquals(5, $arError['CODE'],
			'Error: expects error-code 5, but returned code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
	}

	public function testUpdateCodeOfSystemProperty() {
		$bSuccess = OBX_OrderProperty::update(array(
			'ID' => self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'],
			'CODE' => 'TT_SYSTEM_NUM__',
			OBX_MAGIC_WORD => I_KNOW_WHAT_I_DO
		));
		$this->assertTrue($bSuccess);
		$arProperty = OBX_OrderProperty::getByID(self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'], array(
			'ID', 'CODE', 'NAME', 'PROPERTY_TYPE',
			'DESCRIPTION', 'IS_SYS'));
		$this->assertArrayHasKey('CODE', $arProperty);
		$this->assertEquals('TT_SYSTEM_NUM__', $arProperty['CODE'], 'Error: code of system must be changed with OBX_MAGIC_WORD but it\'s not');

		$bSuccess = OBX_OrderProperty::update(array(
			'ID' => self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'],
			'CODE' => 'TT_SYSTEM_NUMBER',
			OBX_MAGIC_WORD => I_KNOW_WHAT_I_DO
		));
		$this->assertTrue($bSuccess);
		$arProperty = OBX_OrderProperty::getByID(self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID'], array(
			'ID', 'CODE', 'NAME', 'PROPERTY_TYPE',
			'DESCRIPTION', 'IS_SYS'));
		$this->assertArrayHasKey('CODE', $arProperty);
		$this->assertEquals('TT_SYSTEM_NUMBER', $arProperty['CODE'], 'Error: code of system must be changed with OBX_MAGIC_WORD but it\'s not');
	}

	public function testTryingToDeleteSystemProperty() {
		$bSuccess = OBX_OrderProperty::delete(self::$_arPropertyList['TT_SYSTEM_NUMBER']['ID']);
		$this->assertFalse($bSuccess);
		$arError = OBX_OrderProperty::popLastError('ARRAY');
		$this->assertEquals(6, $arError['CODE'],
			'Error: expects error-code 6, but returned code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');

		$bSuccess = OBX_OrderProperty::delete(self::$_arPropertyList['TT_SYSTEM_STRING']['ID']);
		$this->assertFalse($bSuccess);
		$arError = OBX_OrderProperty::popLastError('ARRAY');
		$this->assertEquals(6, $arError['CODE'],
			'Error: expects error-code 6, but returned code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
	}

	public function testUnsetSystemBitFromAll() {
		foreach(self::$_arPropertyList as &$arFields) {
			$bSuccess = OBX_OrderProperty::update(array(
				'CODE' => $arFields['CODE'],
				'IS_SYS' => 'N',
				OBX_MAGIC_WORD => I_KNOW_WHAT_I_DO
			));
			if(!$bSuccess) {
				$arError = OBX_OrderProperty::popLastError('ARRAY');
				$this->assertTrue($bSuccess, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
			}
		}
	}

	public function testDeleteProperty() {
		foreach(self::$_arPropertyList as $propCode => &$arFields) {
			$bSuccess = OBX_OrderProperty::delete($arFields['ID']);
			if(!$bSuccess) {
				$arError = OBX_OrderProperty::popLastError('ARRAY');
				$this->assertTrue($bSuccess, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
			}
		}
	}
}
