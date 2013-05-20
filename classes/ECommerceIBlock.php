<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 **         Artem P. Morozov  aka tashiro     **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

IncludeModuleLangFile(__FILE__);

class ECommerceIBlockDBS extends \OBX_DBSimple
{
	protected $_arTableList = array(
		"E" => "obx_ecom_iblock",
		"B" => "b_iblock",
	);
	protected $_mainTable = 'E';
	protected $_arTableFields = array(
		"IBLOCK_ID"			=> array("E"	=> "IBLOCK_ID"),
		"IBLOCK_CODE"		=> array("B"	=> "CODE"),
		"IBLOCK_NAME"		=> array("B"	=> "NAME"),
		"IBLOCK_TYPE_ID"	=> array("B"	=> "IBLOCK_TYPE_ID"),
		"PRICE_VERSION"		=> array("E"	=> "PRICE_VERSION")
		//"VAT_ID"			=> array("E"	=> "VAT_ID")
	);
	protected $_arTableLinks = array(
		0 => array(
			array("E" => "IBLOCK_ID"),
			array("B" => "ID")
		)
	);
	protected $_mainTablePrimaryKey = "IBLOCK_ID";
	protected $_mainTableAutoIncrement = null;
	protected $_arFilterDefault = array();
	protected $_arSelectDefault = array();
	protected $_arSortDefault = array('ID' => 'ASC');

	protected $_arTableFieldsDefault = array(
		"PRICE_VERSION" => 1
	);
	protected $_arTableFieldsCheck = array();
	protected $_arDBSimpleLangMessages = array();
	protected function __check_PRICE_VERSION(&$fieldValue, &$arCheckData) {
		if( empty($fieldValue) || $fieldValue === 1 || $fieldValue === 2) {
			return true;
		}
		$this->addError(GetMessage("OBX_MARKET_ECOM_ERROR_2"), 2);
		return false;
	}

	protected $_arEComIBlockListCache = null;

	function __construct() {
		$this->_arTableFieldsCheck = array(
			"IBLOCK_ID" => self::FLD_T_IBLOCK_ID | self::FLD_REQUIRED,
			"PRICE_VERSION" => self::FLD_T_INT | self::FLD_CUSTOM_CK | self::FLD_BRK_INCORR
		);
		$this->_arDBSimpleLangMessages = array(
			"REQ_FLD_IBLOCK_ID" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_ECOM_ERROR_1"),
				"CODE" => 1
			)
		);
	}

	protected function _onBeforeDelete(&$arItem) {
		CIBlockPropertyPrice::deleteByFilter(array("IBLOCK_ID" => $arItem["IBLOCK_ID"]));
		return true;
	}
	protected function _onBeforeDeleteByFilter(&$arFilter, &$bCheckExistence, &$arDelete) {
		if( isset($arFilter["IBLOCK_ID"])) {
			CIBlockPropertyPrice::deleteByFilter(array("IBLOCK_ID" => $arFilter["IBLOCK_ID"]));
		}
		// TODO: Дописать удаление связок св-в ИБ с ценами если PRICE_VERSION = 1
		return true;
	}

	public function _onAfterAdd(&$arFields){
		$this->clearCachedList();
		return true;
	}
	public function _onAfterUpdate(&$arFields){
		$this->clearCachedList();
		return true;
	}
	protected function _onAfterDelete(&$arFields) {
		$this->clearCachedList();
		return true;
	}
	protected function _onAfterDeleteByFilter(&$arFields) {
		$this->clearCachedList();
		return true;
	}

	public function getFullList($bResultCDBResult = false) {
		global $DB;
		$sql = <<<SQL
		SELECT
			b.ID AS ID,
			b.CODE AS CODE,
			b.NAME AS NAME,
			b.SORT AS SORT,
			b.IBLOCK_TYPE_ID AS IBLOCK_TYPE_ID,
			(SELECT IF(cb.IBLOCK_ID IS NULL, 'N', 'Y') ) as IS_ECOM,
			cb.PRICE_VERSION as PRICE_VERSION
		FROM
			b_iblock AS b
		LEFT JOIN obx_ecom_iblock AS cb ON (b.ID = cb.IBLOCK_ID)
		ORDER BY
			b.SORT ASC,
			b.ID ASC
SQL;
		$res = $DB->Query($sql, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
		if($bResultCDBResult) {
			return $res;
		}
		$arList = array();
		while( ($arItem = $res->Fetch()) ) {
			$arList[] = $arItem;
		}
		return $arList;
	}

	public function getCachedList() {
		if( $this->_arEComIBlockListCache !== null ) {
			return $this->_arEComIBlockListCache;
		}
		$arEComIBlockList = $this->getListArray();
		$this->_arEComIBlockListCache = array();
		foreach($arEComIBlockList as $arEComIBlock) {
			$this->_arEComIBlockListCache[$arEComIBlock['IBLOCK_ID']] = $arEComIBlock;
		}
		return $this->_arEComIBlockListCache;
	}

	public function clearCachedList(){
		$this->_arEComIBlockListCache = null;
	}

	static public function onIBlockDelete($ID) {
		$that = self::getInstance();
		$that->deleteByFilter(array("IBLOCK_ID" => $ID));
	}

	public function registerModuleDependencies() {
		RegisterModuleDependences(
			"iblock", "OnIBlockDelete",
			"obx.market", __CLASS__, "onIBlockDelete", 510);
	}

	public function unRegisterModuleDependencies() {
		UnRegisterModuleDependences(
			"iblock", "OnIBlockDelete",
			"obx.market", __CLASS__, "onIBlockDelete");
	}
}


class ECommerceIBlock extends \OBX_DBSimpleStatic {
	static public function getFullList($bResultCDBResult = false) {
		return self::getInstance()->getFullList($bResultCDBResult);
	}
	static public function clearCachedList(){
		return self::getInstance()->clearCachedList();
	}
	static public function getCachedList(){
		return self::getInstance()->getCachedList();
	}
	static public function onIBlockDelete($ID) {
		return self::getInstance()->onIBlockDelete($ID);
	}
	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}
	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}
ECommerceIBlock::__initDBSimple(ECommerceIBlockDBS::getInstance());
