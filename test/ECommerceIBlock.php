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

final class OBX_Test_ECommerceIBlock extends OBX_Market_TestCase
{
	static protected  $_arEComIBlockList = array();

	public function testCreatingTestIBlock() {
		$arSitesList = $this->getBXSitesList();
		$this->assertGreaterThan(0, count($arSitesList));

		$this->createTestIBlockTypes(array(self::$_arEComIBlockType));

		$ibLiquidID = $this->importIBlockFromXML(
			__DIR__.'/data/'.LANGUAGE_ID.'/liq.xml',
			self::OBX_TEST_IB_1,
			self::$_arEComIBlockType['ID'],
			$arSitesList
		);
		$this->assertGreaterThan(0, $ibLiquidID);
		$rsIBLiquid = CIBlock::GetByID($ibLiquidID);
		if( $arIBLiquid = $rsIBLiquid->GetNext() ) {
			$this->assertTrue(is_array($arIBLiquid));
			$this->assertGreaterThan(0, count($arIBLiquid));
			self::$_arEComIBlockList[OBX_TEST_IB_1] = $arIBLiquid;
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
