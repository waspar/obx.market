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

final class OBX_Test_ECommerceIBlock extends OBX_Market_TestCase
{
	private $_arEComIBlockType = array();
	static private $_arEComIBlockList = array();

	public function setUp() {
		$this->_arEComIBlockType = array(
			'ID' => 'obx_market_test',
			'NAME' => 'obx_market_test',
			'SECTIONS' => 'Y',
			'IN_RSS' => 'N',
		);
	}

	public function testCreatingTestIBlock() {
		$arSitesList = $this->getBXSitesList();
		$this->assertGreaterThan(0, count($arSitesList));

		$this->createTestIBlockTypes(array($this->_arEComIBlockType));

		$ibLiquidID = $this->importIBlockFromXML(
			__DIR__.'/data/'.LANGUAGE_ID.'/liq.xml',
			'fluid_test',
			$this->_arEComIBlockType['ID'],
			$arSitesList
		);
		$this->assertGreaterThan(0, $ibLiquidID);
		$rsIBLiquid = CIBlock::GetByID($ibLiquidID);
		if( $arIBLiquid = $rsIBLiquid->GetNext() ) {
			$this->assertTrue(is_array($arIBLiquid));
			$this->assertGreaterThan(0, count($arIBLiquid));
			self::$_arEComIBlockList[] = $arIBLiquid;
		}

		$this->assertGreaterThan(0, count(self::$_arEComIBlockList));
	}

	/**
	 * @depends testCreatingTestIBlock
	 */
	public function testSetProductionIBlocks() {
		foreach(self::$_arEComIBlockList as $arIBlock) {
			$newEComIBlockLink = OBX_ECommerceIBlock::add(array('IBLOCK_ID' => $arIBlock['ID']));
			if($newEComIBlockLink == 0) {
				$arError = OBX_ECommerceIBlock::popLastError('ARRAY');
				$this->assertTrue(is_array($arError), 'Can\'t get error data');
				$this->assertArrayHasKey('CODE', $arError, 'Can\'t get error code');
				$this->assertArrayHasKey('TEXT', $arError, 'Can\'t get error text');
				if($arError['CODE'] != OBX_DBSimple::ERR_DUP_PK) {
					$this->assertGreaterThan(0, $newEComIBlockLink, 'Error: code: '.$arError['CODE'].'; text: '.$arError['TEXT'].'.');
				}
				else {
					$newEComIBlockLink = $arIBlock['ID'];
				}
			}
			$this->assertEquals($newEComIBlockLink, $arIBlock['ID'], 'Error: code: '.$arError['CODE'].'; text: '.$arError['TEXT'].'.');
		}
	}

	/**
	 * @depends testSetProductionIBlocks
	 */
	public function testUnSetProductionIBlocks() {
		foreach(self::$_arEComIBlockList as $arIBlock) {
			$bSuccess = OBX_ECommerceIBlock::delete($arIBlock['ID']);
			if( ! $bSuccess ) {
				$arError = OBX_ECommerceIBlock::popLastError('ARRAY');
				$this->assertTrue(is_array($arError), 'Can\'t get error data');
				$this->assertArrayHasKey('CODE', $arError, 'Can\'t get error code');
				$this->assertArrayHasKey('TEXT', $arError, 'Can\'t get error text');
				$this->assertTrue($bSuccess, 'Error: code: '.$arError['CODE'].'; text: '.$arError['TEXT'].'.');
			}
		}
	}
}
