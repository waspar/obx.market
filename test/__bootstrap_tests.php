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
	echo('Warning: Module iblock not installed!!!');
}

if( !CModule::IncludeModule('obx.market') ) {
	echo('Warning: Module OBX: Market not installed!!!');
}

/**
 * TODO: Организовать выполнение тестов через PHPUnit_Framework_TestSuite
 */

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

	/**
	 * Идентификатор тестового пользователя
	 * @var int
	 * @static
	 * @access protected
	 */
	static protected $_arTestUser = array();
	/**
	 * Идентификатор ещё одого тестового пользователя
	 * @var int
	 * @static
	 * @access protected
	 */
	static protected $_arSomeOtherTestUser = array();

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

	protected function callTest($testCaseName, $testName) {
		$fileName = dirname(__FILE__).'/'.$testCaseName.'.php';
		if( !file_exists($fileName) ) {
			$this->fail('ERROR: Can\'t invoke test. File not found');
		}
		require_once $fileName;
		if( substr($testCaseName, 0, 1) == '_' ) {
			$className = 'OBX_Test_Lib'.$testCaseName;
		}
		else {
			$className = 'OBX_Test_'.$testCaseName;
		}
		if( strlen($className)<1 || !class_exists($className) ) {
			$this->fail('ERROR: Can\'t invoke test. TestCase Class not found');
		}
		$TestCase = new $className;
		if( strlen($testName)<1 || !method_exists($TestCase, $testName) ) {
			$this->fail('ERROR: Can\'t invoke test. TestCase Method not found');
		}
		$TestCase->setTestResultObject($this->getTestResultObject());
		$TestCase->setName($testName);
		$TestCase->runTest();
	}

	public function _getTestUser() {
		global $USER;
		$arFields = Array(
			'NAME'              => GetMessage('OBX_MARKET_TEST_USER_1_FNAME'),
			'LAST_NAME'         => GetMessage('OBX_MARKET_TEST_USER_1_LNAME'),
			'EMAIL'             => 'test@test.loc',
			'LID'               => 'ru',
			'ACTIVE'            => 'Y',
			'GROUP_ID'          => array(1,2),
			'PASSWORD'          => '123456',
			'CONFIRM_PASSWORD'  => '123456',
		);
		$rsUser1 = CUser::GetByLogin('__test_basket_user_1');
		$rsUser2 = CUser::GetByLogin('__test_basket_user_2');
		if( $arUser1 = $rsUser1->Fetch() ) {
			self::$_arTestUser = $arUser1;
		}
		else {
			$user = new CUser;
			$arFields['LOGIN'] = '__test_basket_user_1';
			$ID = $user->Add($arFields);
			$this->assertGreaterThan(0, $ID, 'Error: can\'t create test user 1. text: '.$user->LAST_ERROR);
			$rsUser1 = CUser::GetByLogin('__test_basket_user_1');
			if( $arUser1 = $rsUser1->Fetch() ) {
				$this->assertEquals('__test_basket_user_1', $arUser1['LOGIN']);
				self::$_arTestUser = $arUser1;
			}
			else {
				$this->fail('Error: can\'t get test user 1');
			}
		}
		if( $arUser2 = $rsUser2->Fetch() ) {
			self::$_arSomeOtherTestUser = $arUser2;
		}
		else {
			$user = new CUser;
			$arFields['LOGIN'] = '__test_basket_user_2';
			$ID = $user->Add($arFields);
			$this->assertGreaterThan(0, $ID, 'Error: can\'t create test user 2. text: '.$user->LAST_ERROR);
			$rsUser1 = CUser::GetByLogin('__test_basket_user_2');
			if( $arUser2 = $rsUser1->Fetch() ) {
				$this->assertEquals('__test_basket_user_2', $arUser2['LOGIN']);
				self::$_arSomeOtherTestUser = $arUser2;
			}
			else {
				$this->fail('Error: can\'t get test user 2');
			}
		}
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
OBX_Market_TestCase::includeLang(__FILE__);