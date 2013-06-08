<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\CurrencyInfo;

class OBX_Test_CurrencyInfo extends OBX_Market_TestCase {


	/**
	 * TODO: Длописать тест
	 */
	public function testInfo() {
		$CurrencyInfo = CurrencyInfo::getInstance('RUB');
		if($CurrencyInfo != null) {
			$arCurrency = $CurrencyInfo->getFields();
			//$this->assert
			//print_r($arCurrency);
		}
	}
}