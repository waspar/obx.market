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

final class OBX_Test_OrderStatus extends OBX_Market_TestCase
{

	static private $_arStatusList = array();

	static public function setUpBeforeClass() {
		OBX_OrderStatus::deleteByFilter(array('CODE' => 'TT_ACCEPTED'));
		OBX_OrderStatus::deleteByFilter(array('CODE' => 'TT_COMPLETE'));
		OBX_OrderStatus::deleteByFilter(array('CODE' => 'TT_CANCELED'));
	}

	public function testAddStatus() {
		$statusID = OBX_OrderStatus::add(array(
			'CODE' => 'TT_ACCEPTED',
			'NAME' => GetMessage('OBX_Test_TT_ACCEPTED_NAME'),
			'DESCRIPTION' => GetMessage('OBX_Test_TT_ACCEPTED_DESC'),
			'SORT' => 500
		));
		if(!$statusID) {
			$arError = OBX_OrderStatus::popLastError('ARRAY');
			$this->assertGreaterThan(0, $statusID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
		}
		self::$_arStatusList['TT_ACCEPTED'] = $statusID;

		$statusID = OBX_OrderStatus::add(array(
			'CODE' => 'TT_COMPLETE',
			'NAME' => GetMessage('OBX_Test_TT_COMPLETE_NAME'),
			'DESCRIPTION' => GetMessage('OBX_Test_TT_COMPLETE_DESC'),
			'SORT' => 500
		));
		if(!$statusID) {
			$arError = OBX_OrderStatus::popLastError('ARRAY');
			$this->assertGreaterThan(0, $statusID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
		}
		self::$_arStatusList['TT_COMPLETE'] = $statusID;
	}

	public function testAddDuplicateStatus() {
		$statusID = OBX_OrderStatus::add(array(
			'CODE' => 'TT_COMPLETE',
			'NAME' => GetMessage('OBX_Test_TT_COMPLETE_NAME'),
			'DESCRIPTION' => GetMessage('OBX_Test_TT_COMPLETE_DESC'),
			'SORT' => 500
		));
		$this->assertEquals(0, $statusID, 'Error: Adding duplicate status success - it\'s strange.');
		$arError = OBX_OrderStatus::popLastError('ARRAY');
		$this->assertEquals(3, $arError['CODE'],
			'Expected error code = 3, but returned error code = "'.$arError['CODE'].'"'
				.' Text: "'.$arError['TEXT'].'"'
		);
	}

	public function testUpdateStatus() {
		
	}

	public function testDeleteStatus() {

	}

	public function testDeleteNonexistentStatus() {

	}

	public function testAddSystemStatus() {
		$statusID = OBX_OrderStatus::add(array(
			'CODE' => 'TT_CANCELED',
			'NAME' => GetMessage('OBX_Test_TT_CANCELED_NAME'),
			'DESCRIPTION' => GetMessage('OBX_Test_TT_CANCELED_DESC'),
			'SORT' => 500,
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => I_KNOW_WHAT_I_DO
		));
		if(!$statusID) {
			$arError = OBX_OrderStatus::popLastError('ARRAY');
			$this->assertGreaterThan(0, $statusID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
		}
		$arSystemStatus = OBX_OrderStatus::getListArray(null, array('CODE' => 'TT_CANCELED'), null. null, array('ID', 'CODE', 'IS_SYS'));
		$this->assertGreaterThan(0, count($arSystemStatus));
		$arSystemStatus = $arSystemStatus[0];
		$this->assertEquals('Y', $arSystemStatus['IS_SYS']);
		self::$_arStatusList['TT_CANCELED'] = $statusID;
	}

	public function testTryToDeleteSystemStatus() {
		$bSuccess = OBX_OrderStatus::delete(self::$_arStatusList['TT_CANCELED']);
		$this->assertFalse($bSuccess, 'Error: delete system status passed. It\'s wrong');
		$arError = OBX_OrderStatus::popLastError('ARRAY');
		$this->assertEquals(1, $arError['CODE'], 'Expects status code = 1, but returned status code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
	}

	public function testRemoveSystemBitFromStatus() {
		$arFields = array(
			'ID' => self::$_arStatusList['TT_CANCELED'],
			'IS_SYS' => 'N',
		);
		// Пробуем удалить так, как бы это сделал пользовтель
		$bSuccess = OBX_OrderStatus::update($arFields);
		$this->assertTrue($bSuccess);
		$arStatus = OBX_OrderStatus::getByID(self::$_arStatusList['TT_CANCELED'], array('ID', 'CODE', 'IS_SYS'));
		$this->assertNotEmpty($arStatus);
		$this->assertEquals('Y', $arStatus['IS_SYS']);

		// Добавляем волшебное слово
		$arFields[OBX_MAGIC_WORD] = I_KNOW_WHAT_I_DO;
		$bSuccess = OBX_OrderStatus::update($arFields);
		$this->assertTrue($bSuccess, 'Error: can\'t remove system-bit from the order status');
		$arStatus = OBX_OrderStatus::getByID(self::$_arStatusList['TT_CANCELED'], array('ID', 'CODE', 'IS_SYS'));
		$this->assertNotEmpty($arStatus);
		$this->assertEquals('TT_CANCELED', $arStatus['CODE'], 'Error: getByID() return wrong status');
		$this->assertEquals('N', $arStatus['IS_SYS'], 'Error: removing system-bit not passed. Field "IS_SYS" not equal "N"');
	}

	public function testDeleteSystemStatus() {
		$bSuccess = OBX_OrderStatus::delete(self::$_arStatusList['TT_ACCEPTED']);
		$arError = OBX_OrderStatus::popLastError('ARRAY');
		$this->assertTrue($bSuccess, 'Error: can\'t delete status. code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');

		$bSuccess = OBX_OrderStatus::delete(self::$_arStatusList['TT_COMPLETE']);
		$arError = OBX_OrderStatus::popLastError('ARRAY');
		$this->assertTrue($bSuccess, 'Error: can\'t delete status. code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');

		$bSuccess = OBX_OrderStatus::delete(self::$_arStatusList['TT_CANCELED']);
		$arError = OBX_OrderStatus::popLastError('ARRAY');
		$this->assertTrue($bSuccess, 'Error: can\'t delete status. code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
	}
}
