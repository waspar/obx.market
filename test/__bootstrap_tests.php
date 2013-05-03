<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

define('DBPersistent', true);
$curDir = dirname(__FILE__);
$wwwRootStrPos = strpos($curDir, '/bitrix/modules/obx.market');
if( $wwwRootStrPos === false ) {
	die('Can\'t find www-root');
}

$_SERVER['DOCUMENT_ROOT'] = substr($curDir, 0, $wwwRootStrPos);
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/bootstrap_tests.php');
global $USER;
global $DB;
// Без этого фикса почему-то не работает. Не видит это значение в include.php модуля
global $DBType;
$DBType = strtolower($DB->type);

$USER->Authorize(1);
if( !CModule::IncludeModule('iblock') ) {
	die('Module iblock not installed');
}

if( !CModule::IncludeModule('obx.market') ) {
	die('Module OBX: Market not installed');
}

abstract class OBX_Market_TestCase extends PHPUnit_Framework_TestCase {
	protected $backupGlobals = false;
	// pr0n1x: 2013-01-25:
	// если переменная $backupGlobals равна true то битрикс будет косячить на каждом тесте, где есть обращение к БД
	// косяк происходит в предшествующем тесте, в котором бэкапятся гблобальные перменные.
	// Потому !в каждом! тесте надо вызывать $this->setBackupGlobals(false);
	// Или просто сразу сделать переменную в false :)
	//
	// Этот метод тож имеет какое-то значение, но и без него работало нормально
	//$this->setPreserveGlobalState(false);
	//protected $preserveGlobalState = false;


	static protected  $_arTestIBlockType = array(
		'ID' => 'obx_market_test',
		'NAME' => 'obx_market_test',
		'SECTIONS' => 'Y',
		'IN_RSS' => 'N',
		'LANG' => array(
			'en' => array(
				'NAME' => 'Test infoblock',
				'SECTION_NAME'=>'Sections',
				'ELEMENT_NAME'=>'Products'
			),
			'en' => array(
				'NAME' => 'Test infoblock',
				'SECTION_NAME'=>'Sections',
				'ELEMENT_NAME'=>'Products'
			)
		)
	);
	const OBX_TEST_PRICE_CODE = 'TEST_PRICE';
	const OBX_TEST_IB_1 = 'fluid_test';
	const OBX_TEST_IB_1_PRICE_PROP_CODE = 'PRICE';

	static public function includeLang($file) {
		$file = str_replace(array('\\', '//'), '/', $file);
		$fileName = substr($file, strrpos($file, '/'));
		$langFile = __DIR__.'/lang/'.LANGUAGE_ID.'/'.$fileName;
		if( file_exists($langFile) ) {
			__IncludeLang($langFile);
			return true;
		}
		return false;
	}

	protected function setUp() {

	}

	protected function getBXLangList() {
		$rsLang = CLanguage::GetList($by='sort', $sort='asc', $arLangFilter=array('ACTIVE' => 'Y'));
		$arLangList = array();
		while( $arLang = $rsLang->Fetch() ) {
			$arLangList[$arLang['ID']] = $arLang;
		}
		return $arLangList;
	}

	protected function getBXSitesArray() {
		$rsSites = CSite::GetList($by='sort', $order='desc', array(''));
		$arSites = array();
		while ($arSite = $rsSites->Fetch()) {
			$arSites[$arSite['LID']] = $arSite;
		}
		return $arSites;
	}
	protected function getBXSitesList() {
		$arSites = $this->getBXSitesArray();
		return array_keys($arSites);
	}

	protected function createTestIBlockTypes($arIBlockTypes) {
		global $DB;
		$DB->StartTransaction();
		foreach($arIBlockTypes as &$arIBType) {
			$rsExists = CIBlockType::GetByID($arIBType['ID']);
			if( $rsExists->GetNext() ) {
				continue;
			}
			$obBlockType = new CIBlockType;
			$ibTypeID = $obBlockType->Add($arIBType);
			$bSuccess = (strlen($ibTypeID)>0)?true:false;
			if(!$bSuccess) {
				$DB->Rollback();
				$this->assertTrue($bSuccess, 'Error: '.$obBlockType->LAST_ERROR);
				return 0;
			}
		}
		$DB->Commit();
		return $ibTypeID;
	}

	protected function importIBlockFromXML($xmlFile, $iblockCode, $iblockType, $siteID, $permissions = Array()) {
		$obIBlock = new CIBlock;
		$rsIBlock = $obIBlock->GetList(array(), array('CODE' => $iblockCode, 'TYPE' => $iblockType, 'SITE_ID' => $siteID));
		if( $arIBlock = $rsIBlock->Fetch() ) {
			return $arIBlock['ID'];
		}
		if( !is_array($siteID) ) {
			$siteID = Array($siteID);
		}

		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/'.strtolower($GLOBALS['DB']->type).'/cml2.php');
		ImportXMLFile($xmlFile, $iblockType, $siteID, $section_action = 'N', $element_action = 'N');

		$rsIBlock = $obIBlock->GetList(array(), array('CODE' => $iblockCode, 'TYPE' => $iblockType, 'SITE_ID' => $siteID));
		if( $arIBlock = $rsIBlock->Fetch() ) {
			return $arIBlock['ID'];
		}
		return 0;
	}
	protected function createTestIBlocks($arIBlockList) {
		global $DB;
		$DB->StartTransaction();
		foreach($arIBlockList as &$arIB) {

		}
		$DB->Commit();
	}
}
