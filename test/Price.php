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

OBX_Market_TestCase::includeLang(__FILE__);

class OBX_Test_Price extends OBX_Market_TestCase
{
	public function setUp() {

	}

	public function testAddNewPrice() {
		$newPriceID = OBX_Price::add(array(
			'CODE' => 'TEST_PRICE',
			'NAME' => GetMessage('OBX_MARKET_TEST_PRICE_1'),
			'CURRENCY' => 'RUB',
			''
		));
	}
}
