<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

class OBX_Test_CurrencyInfo extends OBX_Market_TestCase {


	/**
	 * TODO: Длописать тест
	 */
	public function testInfo() {
		$CurrencyInfo = OBX_CurrencyInfo::getInstance('RUB');
		if($CurrencyInfo != null) {
			$arCurrency = $CurrencyInfo->getFields();
			//$this->assert
			print_r($arCurrency);
		}
	}
}