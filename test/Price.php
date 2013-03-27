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

class OBX_Test_Price extends OBX_Market_TestCase
{
	public function setUp() {

	}

	public function testAddNewPrice() {
		$newPriceID = OBX_Price::add(array(
			'CODE' => self::OBX_TEST_PRICE_CODE,
			'NAME' => GetMessage('OBX_MARKET_TEST_PRICE_1'),
			'CURRENCY' => 'RUB',
			''
		));
	}
}
