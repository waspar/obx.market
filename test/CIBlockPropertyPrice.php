<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\Price as Price;
use OBX\Market\Price as OBX_Price;
use OBX\Market\PriceDBS as PriceDBS;
use OBX\Market\PriceDBS as OBX_PriceDBS;
use OBX\Market\ECommerceIBlock;
use OBX\Market\ECommerceIBlockDBS;
use OBX\Market\CIBlockPropertyPrice;
use OBX\Market\CIBlockPropertyPriceDBS;

final class OBX_Test_CIBlockPropertyPrice extends OBX_Market_TestCase
{

	static private $_arTestIB = array();
	static private $_arTestPrice = array();
	static private $_arIBPriceProp = array();

	public function testPriceAndPropExisting() {
		$arPriceList = OBX_Price::getListArray(null, array('CODE' => self::OBX_TEST_IB_1_PRICE_PROP_CODE));
		$priceNotFound = 'Error: price "'.self::OBX_TEST_IB_1_PRICE_PROP_CODE.'" not found';
		$this->assertTrue(is_array($arPriceList), $priceNotFound);
		$this->assertTrue((count($arPriceList)>0), $priceNotFound);
		$this->assertGreaterThan(0, $arPriceList[0]['ID']);
		self::$_arTestPrice = $arPriceList[0];

		$errTestIBNotFound = 'Error: test iblock not found';
		$rsTestIB = CIBlock::GetList(false, array('CODE' => self::OBX_TEST_IB_1));
		$arTestIB = $rsTestIB->GetNext();
		$this->assertNotEmpty($arTestIB, $errTestIBNotFound);
		$this->assertArrayHasKey('IBLOCK_TYPE_ID', $arTestIB, $errTestIBNotFound);
		$this->assertEquals(self::$_arTestIBlockType['ID'], $arTestIB['IBLOCK_TYPE_ID'], $errTestIBNotFound);
		self::$_arTestIB = $arTestIB;

		$errIBPricePropNotFound = 'Error: test iblock property "'.self::OBX_TEST_IB_1_PRICE_PROP_CODE.'" not found';
		$arIBPriceProp = array();
		$idIBPriceProp = OBX_Tools::getPropIdByCode($arTestIB['ID'], self::OBX_TEST_IB_1_PRICE_PROP_CODE, $arIBPriceProp);
		$this->assertGreaterThan(0, $idIBPriceProp, $errIBPricePropNotFound);
		$this->assertTrue(is_array($arIBPriceProp), $errIBPricePropNotFound);
		$this->assertArrayHasKey('IBLOCK_ID', $arIBPriceProp, $errIBPricePropNotFound);
		$this->assertEquals($arTestIB['ID'], $arIBPriceProp['IBLOCK_ID'], $errIBPricePropNotFound);
		self::$_arIBPriceProp = $arIBPriceProp;
	}

	/**
	 * Попробовать задать свойство тестового инфоблока
	 * как цену в _НЕ_ торговом инфоблоке
	 * @depends testPriceAndPropExisting
	 */
	public function testTryToAddPricePropLinkToNotEComIB() {
		ECommerceIBlock::delete(self::$_arTestIB['ID']);

		$pricePropLinkID = CIBlockPropertyPrice::add(array(
			'PRICE_ID' => self::$_arTestPrice['ID'],
			'IBLOCK_ID' => self::$_arTestIB['ID'],
			'IBLOCK_PROP_ID' => self::$_arIBPriceProp['ID']
		));
		$arError = CIBlockPropertyPrice::popLastError('ARRAY');
		$this->assertEquals(0, $pricePropLinkID, 'Error: IBlock isn\'t ECommerce, but Adding is success');
		$this->assertEquals(6, $arError['CODE'],
			'Error code must be equal "6", but: code = "'.$arError['CODE'].'", text = "'.$arError['TEXT'].'"');
	}

	/**
	 * Добаивть тестовый в ECommerce-список (сделать его торговым)
	 * @depends testPriceAndPropExisting
	 */
	public function testAddTestIBToECommerce() {
		$iblockID = ECommerceIBlock::add(array('IBLOCK_ID' => self::$_arTestIB['ID']));
		if( $iblockID == 0 ) {
			$arError = ECommerceIBlock::popLastError('ARRAY');
		}
		$this->assertGreaterThan(0, $iblockID, 'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'".');
		$this->assertEquals(self::$_arTestIB['ID'], $iblockID, 'Error: Very strange behavior!');
	}

	/**
	 * @depends testPriceAndPropExisting
	 */
	public function testAddPricePropLink() {
		$pricePropLinkID = CIBlockPropertyPrice::add(array(
			'PRICE_ID' => self::$_arTestPrice['ID'],
			'IBLOCK_ID' => self::$_arTestIB['ID'],
			'IBLOCK_PROP_ID' => self::$_arIBPriceProp['ID']
		));
		if($pricePropLinkID<1) {
			$arError = CIBlockPropertyPrice::popLastError('ARRAY');
			$this->assertGreaterThan(0, $pricePropLinkID,
				'Error: code: "'.$arError['CODE'].'"; text: "'.$arError['TEXT'].'"');
		}
	}

	/**
	 * @depends testAddPricePropLink
	 */
	public function testAddDuplicatePricePropLink(){
		$pricePropLinkID = CIBlockPropertyPrice::add(array(
			'PRICE_ID' => self::$_arTestPrice['ID'],
			'IBLOCK_ID' => self::$_arTestIB['ID'],
			'IBLOCK_PROP_ID' => self::$_arIBPriceProp['ID']
		));
		$arError = CIBlockPropertyPrice::popLastError('ARRAY');
		$this->assertEquals(0, $pricePropLinkID);
		// 4 - error code of duplicate price property link
		$this->assertEquals(4, $arError['CODE'], 'Error: error-code must be equal 4, but it not');
	}

	/**
	 * @depends testAddPricePropLink
	 */
	public function testRemovePricePropLink() {
		$bSuccess = CIBlockPropertyPrice::delete(13);
		$arError = CIBlockPropertyPrice::popLastError('ARRAY');
		$this->assertFalse($bSuccess);
		$this->assertEquals(CIBlockPropertyPriceDBS::ERR_CANT_DEL_WITHOUT_PK, $arError['CODE']);

		$bSuccess = CIBlockPropertyPrice::deleteByFilter(array(
			'IBLOCK_ID' => self::$_arTestIB['ID'],
			'IBLOCK_PROP_ID' => self::$_arIBPriceProp['ID']
		));
		if(!$bSuccess) {
			$arError = CIBlockPropertyPrice::popLastError('ARRAY');
			$this->assertTrue($bSuccess, 'Error: code: '.$arError['CODE'].'; text: '.$arError['TEXT'].'.');
		}
	}
}
