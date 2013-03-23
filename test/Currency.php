<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ***************************************/

OBX_Market_TestCase::includeLang(__FILE__);

final class OBX_Test_Currency extends OBX_Market_TestCase
{
	protected $backupGlobals = false;
	//$this->setBackupGlobals(false);
	//$this->setPreserveGlobalState(false);

	private $_CurrencyDBS = null;
	private $_arResult = array();
	private $_arTestCurrencies = array();
	public function setUp() {
		$this->_CurrencyDBS = OBX_CurrencyDBS::getInstance();
		$this->_arTestCurrencies = array(
			'TUG' => array(
				'COURSE' => '1',
				'RATE' => '1',
				'IS_DEFAULT' => 'N',
				'FORMAT' => array(
					'ru' => array(
						'NAME' => 'Тугрики',
						'FORMAT' => '# туг.',
						'THOUSANDS_SEP' => ' ',
						'DEC_POINT' => ','
					),
					'en' => array(
						'NAME' => 'Tugriki',
						'FORMAT' => '# tug.',
						'THOUSANDS_SEP' => '\'',
						'DEC_POINT' => '.'
					)
				)
			),
			'TUK' => array(
				'COURSE' => '1',
				'RATE' => '1',
				'IS_DEFAULT' => 'N',
				'FORMAT' => array(
					'ru' => array(
						'NAME' => 'Тукрики',
						'FORMAT' => '# туг.',
						'THOUSANDS_SEP' => ' ',
						'DEC_POINT' => ','
					),
					'en' => array(
						'NAME' => 'Tukriki',
						'FORMAT' => '# tug.',
						'THOUSANDS_SEP' => '\'',
						'DEC_POINT' => '.'
					)
				)
			),
		);
	}

	public function testCreateCurrency() {
		$this->_arResult['CREATE_CURRENCY'] = array();
		foreach($this->_arTestCurrencies as $currency => &$arCurrency) {
			$this->_arResult['CREATE_CURRENCY'][$currency] = array(
				'SUCCESS' => false,
				'ERROR' => array()
			);
			$this->_arResult['CREATE_CURRENCY'][$currency]['SUCCESS'] = $this->_CurrencyDBS->add(array(
				'CURRENCY' => $currency,
				'COURSE' => $arCurrency['COURSE'],
				'RATE' => $arCurrency['RATE']
			));
			if( ! $this->_arResult['CREATE_CURRENCY'][$currency]['SUCCESS'] ) {
				$this->_arResult['CREATE_CURRENCY'][$currency]['ERROR'] = OBX_Currency::popLastError('ARRAY');
				if($this->_arResult['CREATE_CURRENCY'][$currency]['ERROR']['CODE'] != 2) {
					$this->assertTrue(false, $this->_arResult['CREATE_CURRENCY'][$currency]['ERROR']['TEXT']);
				}
			}
		}
	}

	/**
	 * @depends testCreateCurrency
	 */
	public function testCreateCurrencyDuplicate(){
		$this->_arResult['CREATE_CURRENCY'] = array();
		foreach($this->_arTestCurrencies as $currency => &$arCurrency) {
			$this->_arResult['CREATE_CURRENCY'][$currency] = array(
				'SUCCESS' => false,
				'ERROR' => array()
			);
			$this->_arResult['CREATE_CURRENCY'][$currency]['SUCCESS'] = $this->_CurrencyDBS->add(array(
				'CURRENCY' => $currency,
				'COURSE' => $arCurrency['COURSE'],
				'RATE' => $arCurrency['RATE']
			));
			$this->assertFalse($this->_arResult['CREATE_CURRENCY'][$currency]['SUCCESS'], GetMessage('testCreateCurrencyDuplicate_1'));
			if( ! $this->_arResult['CREATE_CURRENCY'][$currency]['SUCCESS'] ) {
				$this->_arResult['CREATE_CURRENCY'][$currency]['ERROR'] = OBX_Currency::popLastError('ARRAY');
				$this->assertEquals(
					2, $this->_arResult['CREATE_CURRENCY'][$currency]['ERROR']['CODE'],
					GetMessage('testCreateCurrencyDuplicate_2', array(
						'#EXPECTED_CODE#' => 2,
						'#CODE#' => $this->_arResult['CREATE_CURRENCY'][$currency]['ERROR']['CODE'],
						'#TEXT#' => $this->_arResult['CREATE_CURRENCY'][$currency]['ERROR']['TEXT']
					))
				);
			}
		}
	}

	/**
	 * @depends testCreateCurrency
	 */
	public function testCurrencyGetList() {
		$arCurrencyList = OBX_Currency::getListArray();
		$countCurrencyList = count($arCurrencyList);
		$this->assertNotNull($countCurrencyList, 'Currency list is empty');
	}

	public function testUpdateCurrency() {
		$bSuccess = OBX_Currency::update(array(
			'CURRENCY' => 'TUK',
			'COURSE' => '2',
			'RATE' => '2',
			'IS_DEFAULT' => 'Y'
		));
		if($bSuccess) {
			$arError = OBX_Currency::popLastError('ARRAY');
		}
		$this->assertTrue(
			$bSuccess,
			GetMessage('testUpdateCurrency_1').'. '
				.GetMessage('OBX_ERROR_CODE').': '.$arError['CODE'].'. '
				.GetMessage('OBX_ERROR_TEXT').': '.$arError['TEXT'].'.'
		);
		$arUpdatedCurrency = OBX_Currency::getByID('TUK');
		$this->assertArrayHasKey('CURRENCY', $arUpdatedCurrency);
		$this->assertArrayHasKey('COURSE', $arUpdatedCurrency);
		$this->assertArrayHasKey('RATE', $arUpdatedCurrency);
		$this->assertArrayHasKey('IS_DEFAULT', $arUpdatedCurrency);
		$this->assertEquals(2, $arUpdatedCurrency['COURSE']);
		$this->assertEquals(2, $arUpdatedCurrency['RATE']);
		$this->assertEquals('Y', $arUpdatedCurrency['IS_DEFAULT']);
	}

	public function testUpdateNonexistentCurrency() {
		$bSuccess = OBX_Currency::update(array(
			'CURRENCY' => 'NOC',
			'COURSE' => '2',
			'RATE' => '2',
			'IS_DEFAULT' => 'Y'
		));
		$this->assertFalse($bSuccess);
		$arError = OBX_Currency::popLastError('ARRAY');
		$this->assertArrayHasKey('CODE', $arError);
		$this->assertArrayHasKey('TEXT', $arError);
		$this->assertEquals(3, $arError['CODE']);
	}

	public function testSetDefaultCurrency() {
		$bSuccess = OBX_Currency::setDefault('TUG');
		$this->assertTrue($bSuccess);

		$arTUG = OBX_Currency::getByID('TUG');
		$arTUK = OBX_Currency::getByID('TUK');

		$this->assertArrayHasKey('CURRENCY', $arTUG);
		$this->assertArrayHasKey('COURSE', $arTUG);
		$this->assertArrayHasKey('RATE', $arTUG);
		$this->assertArrayHasKey('IS_DEFAULT', $arTUG);

		$this->assertArrayHasKey('CURRENCY', $arTUK);
		$this->assertArrayHasKey('COURSE', $arTUK);
		$this->assertArrayHasKey('RATE', $arTUK);
		$this->assertArrayHasKey('IS_DEFAULT', $arTUK);

		$this->assertEquals('Y', $arTUG['IS_DEFAULT']);
		$this->assertEquals('N', $arTUK['IS_DEFAULT']);
	}

	public function testGetDefaultCurrency() {
		$defaultCurrency = OBX_Currency::getDefault();
		$arDefaultCurrency = OBX_Currency::getDefaultArray();
		$this->assertNotEmpty($defaultCurrency);
		$this->assertEquals('TUG', $defaultCurrency);
		$this->assertArrayHasKey('CURRENCY', $arDefaultCurrency);
		$this->assertArrayHasKey('COURSE', $arDefaultCurrency);
		$this->assertArrayHasKey('RATE', $arDefaultCurrency);
		$this->assertArrayHasKey('IS_DEFAULT', $arDefaultCurrency);

		$this->assertEquals('Y', $arDefaultCurrency['IS_DEFAULT']);
		$this->assertEquals($defaultCurrency, $arDefaultCurrency['CURRENCY']);
	}

	/**
	 * @depends testSetDefaultCurrency
	 * @depends testUpdateCurrency
	 */
	public function testSetDefaultCurrencyViaUpdate() {
		$bSuccess = OBX_Currency::setDefault('TUK');
		$this->assertTrue($bSuccess);
		$arTUG = OBX_Currency::getByID('TUG');
		$arTUK = OBX_Currency::getByID('TUK');
		$this->assertEquals('N', $arTUG['IS_DEFAULT']);
		$this->assertEquals('Y', $arTUK['IS_DEFAULT']);

		$bSuccess = OBX_Currency::update(array(
			'CURRENCY' => 'TUG',
			'IS_DEFAULT' => 'Y'
		));
		$this->assertTrue($bSuccess);
		$arTUG = OBX_Currency::getByID('TUG');
		$arTUK = OBX_Currency::getByID('TUK');
		$this->assertEquals('Y', $arTUG['IS_DEFAULT']);
		$this->assertEquals('N', $arTUK['IS_DEFAULT']);
	}

	/**
	 * @depends testCurrencyGetList
	 */
	public function testCreateCurrencyFormat() {
		$arLangList = $this->getBXLangList();
		foreach($arLangList as &$arLang) {
			foreach($this->_arTestCurrencies as $arTestCurrency) {
				$newFormatID = OBX_CurrencyFormat::add(array(
					'LANGUAGE_ID' => $arLang['LID'],
					'CURRENCY' => 'TUG',
					'NAME' => $arTestCurrency['FORMAT'][$arLang['LID']]['NAME']
				));
				if($newFormatID == 0) {
					$arError = OBX_CurrencyFormat::popLastError('ARRAY');
					if($arError['CODE'] != 4) {
						$this->assertGreaterThan(
							0, $newFormatID,
							GetMessage('testCreateCurrencyFormat_1').'. '
								.GetMessage('OBX_ERROR_CODE').': '.$arError['CODE'].'. '
								.GetMessage('OBX_ERROR_TEXT').': '.$arError['TEXT'].'.'
						);
					}
				}
			}
		}
	}

	/**
	 * @depends testCreateCurrencyFormat
	 */
	public function testCurrencyFormatGetList() {
		$arLangList = $this->getBXLangList();
		$arFilter = array('CURRENCY' => array('TUG', 'TUK'));
		// Выборка без джойнов (последний аргумент false). Соотв если формата нет, то и значений по умолчанию не получим
		$arFormatList = OBX_CurrencyFormat::getListArray(null, $arFilter, null, null, null, false);
		$this->assertTrue(is_array($arFormatList));
		$countFormatList = count($arFormatList);
		$this->assertGreaterThan(0, $countFormatList);
	}

	public function getListGroupedByLang() {

	}

	/**
	 * @depends testCurrencyFormatGetList
	 */
	public function testDeleteCurrencyFormat() {
		$arFilter = array('CURRENCY' => array('TUG', 'TUK'));
		// Выборка без джойнов (последний аргумент false). Соотв если формата нет, то и значений по умолчанию не получим
		$arFormatList = OBX_CurrencyFormat::getListArray(null, $arFilter, null, null, null, false);
		$this->assertTrue(is_array($arFormatList));
		$countFormatList = count($arFormatList);
		$this->assertGreaterThan(0, $countFormatList);

		foreach($arFormatList as &$arFormat) {
			$this->assertTrue(is_array($arFormat));
			$this->assertArrayHasKey('ID', $arFormat);
			$bSuccess = OBX_CurrencyFormat::delete($arFormat['ID']);
			if(!$bSuccess) {
				$arError = OBX_Currency::popLastError('ARRAY');
				$this->assertTrue($bSuccess,
					GetMessage('testDeleteCurrencyFormat_1').'. '
						.GetMessage('OBX_ERROR_CODE').': '.$arError['CODE'].'. '
						.GetMessage('OBX_ERROR_TEXT').': '.$arError['TEXT'].'.'
				);
			}
		}
		$arFormatList = OBX_CurrencyFormat::getListArray(null, $arFilter, null, null, null, false);
		$this->assertTrue(is_array($arFormatList));
		$countFormatList = count($arFormatList);
		$this->assertEquals(0, $countFormatList);
	}

	/**
	 * @depends testCurrencyFormatGetList
	 */
	public function testDeleteCurrencyFormatIfNotExists() {
		$bSuccess = OBX_CurrencyFormat::delete(8273687);
		$this->assertFalse($bSuccess);
		$arError = OBX_CurrencyFormat::popLastError('ARRAY');
		$this->assertTrue(is_array($arError));
		$this->assertEquals(5, $arError['CODE']);
		$this->assertGreaterThan(0, strlen($arError['TEXT']));
	}

	/**
	 * @depends testCreateCurrencyFormat
	 * @depends testCurrencyFormatGetList
	 * @depends testDeleteCurrencyFormat
	 */
	public function testGetDefaultCurrencyFormatIfNotExists() {
		$arLangList = $this->getBXLangList();
		$countLangList = count($arLangList);
		$arCurrencyFormatList = OBX_CurrencyFormat::getListArray(null, array(
			'CURRENCY' => array('TUG', 'TUK')
		));
		$countCurrencyFormat = count($arCurrencyFormatList);

		$this->assertNotNull($countLangList);
		$this->assertNotNull($countCurrencyFormat);
		$this->assertEquals($countLangList*2, $countCurrencyFormat);
		foreach($arCurrencyFormatList as &$arCurrencyFormat) {
			$this->assertArrayHasKey('CURRENCY', $arCurrencyFormat);
			$this->assertArrayHasKey('LANGUAGE_ID', $arCurrencyFormat);
			$this->assertArrayHasKey($arCurrencyFormat['LANGUAGE_ID'], $arLangList);
			$this->assertArrayHasKey('FORMAT', $arCurrencyFormat);
			$this->assertArrayHasKey('DEC_POINT', $arCurrencyFormat);
			$this->assertArrayHasKey('DEC_PRECISION', $arCurrencyFormat);
			$this->assertArrayHasKey('THOUSANDS_SEP', $arCurrencyFormat);
			$this->assertGreaterThan(0, strlen($arCurrencyFormat['FORMAT']));
			$this->assertGreaterThan(0, strlen($arCurrencyFormat['DEC_POINT']));
			$this->assertGreaterThan(0, strlen($arCurrencyFormat['DEC_PRECISION']));
			$this->assertNotNull($arCurrencyFormat['THOUSANDS_SEP']);
		}
	}

	/**
	 * @depends testCreateCurrency
	 */
	public function testDeleteCurrency() {
		foreach($this->_arTestCurrencies as $currency => $arCurrency) {
			$bSuccess = OBX_Currency::delete($currency);
			if(!$bSuccess) {
				$arError = OBX_Currency::popLastError('ARRAY');
				$this->assertTrue($bSuccess,
					GetMessage('testDeleteCurrency_1').'. '
						.GetMessage('OBX_ERROR_CODE').': '.$arError['CODE'].'. '
						.GetMessage('OBX_ERROR_TEXT').': '.$arError['TEXT'].'.'
				);
			}
		}
	}
}

