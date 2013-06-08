<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

use OBX\Core\Tools;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;

IncludeModuleLangFile(__FILE__);

class ECommerceIBlockDBS extends DBSimple
{
	protected $_arTableList = array(
		'E' => 'obx_ecom_iblock',
		'B' => 'b_iblock',
	);
	protected $_mainTable = 'E';
	protected $_arTableFields = array(
		'IBLOCK_ID'				=> array('E'	=> 'IBLOCK_ID'),
		'IBLOCK_CODE'			=> array('B'	=> 'CODE'),
		'IBLOCK_NAME'			=> array('B'	=> 'NAME'),
		'IBLOCK_TYPE_ID'		=> array('B'	=> 'IBLOCK_TYPE_ID'),
		'VERSION'				=> array('E'	=> 'VERSION'),
		'WEIGHT_VAL_PROP_ID'	=> array('E'	=> 'WEIGHT_VAL_PROP_ID'),
		'DISCOUNT_VAL_PROP_ID'	=> array('E'	=> 'DISCOUNT_VAL_PROP_ID'),
		//'VAT_ID'				=> array('E'	=> 'VAT_ID'),
		//'VAT_VAL_PROP_ID'		=> array('E'	=> 'VAT_VAL_PROP_ID'),
	);
	protected $_arTableLinks = array(
		0 => array(
			array('E' => 'IBLOCK_ID'),
			array('B' => 'ID')
		)
	);
	protected $_mainTablePrimaryKey = 'IBLOCK_ID';
	protected $_mainTableAutoIncrement = null;
	protected $_arFilterDefault = array();
	protected $_arSelectDefault = array();
	protected $_arSortDefault = array('ID' => 'ASC');

	protected $_arTableFieldsDefault = array(
		'VERSION' => 1
	);
	protected $_arTableFieldsCheck = array();
	protected $_arDBSimpleLangMessages = array();
	protected function __check_VERSION(&$fieldValue, &$arCheckData) {
		if( empty($fieldValue) || $fieldValue === 1 || $fieldValue === 2) {
			return true;
		}
		$this->addError(GetMessage('OBX_MARKET_ECOM_ERROR_2'), 2);
		return false;
	}

	protected $_arEComIBlockListCache = null;

	function __construct() {
		$this->_arTableFieldsCheck = array(
			'IBLOCK_ID' => self::FLD_T_IBLOCK_ID | self::FLD_REQUIRED,
			'VERSION' => self::FLD_T_INT | self::FLD_CUSTOM_CK | self::FLD_BRK_INCORR,
			'WEIGHT_VAL_PROP_ID' => self::FLD_T_IBLOCK_PROP_ID,
			'DISCOUNT_VAL_PROP_ID' => self::FLD_T_IBLOCK_PROP_ID
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_IBLOCK_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_ECOM_ERROR_1'),
				'CODE' => 1
			)
		);
	}

	protected function _onBeforeAdd(&$arFields, &$arCheckResult){
		if( array_key_exists('WEIGHT_VAL_PROP_ID', $arCheckResult)
			&& $arCheckResult['WEIGHT_VAL_PROP_ID']['IS_CORRECT'] == true
			&& $arFields['IBLOCK_ID'] != $arCheckResult['WEIGHT_VAL_PROP_ID']['CHECK_DATA']['IBLOCK_ID']
		) {
			$this->addWarning(GetMessage('OBX_MARKET_ECOM_WARNING_1'), 1);
			unset($arFields['WEIGHT_VAL_PROP_ID']);
		}
		if( array_key_exists('DISCOUNT_VAL_PROP_ID', $arCheckResult)
			&& $arCheckResult['DISCOUNT_VAL_PROP_ID']['IS_CORRECT'] == true
			&& $arFields['IBLOCK_ID'] != $arCheckResult['DISCOUNT_VAL_PROP_ID']['CHECK_DATA']['IBLOCK_ID']
		) {
			$this->addWarning(GetMessage('OBX_MARKET_ECOM_WARNING_2'), 2);
			unset($arFields['DISCOUNT_VAL_PROP_ID']);
		}
		if(
			   array_key_exists('WEIGHT_VAL_PROP_ID', $arCheckResult)
			&& $arCheckResult['WEIGHT_VAL_PROP_ID']['IS_CORRECT'] == true
			&& array_key_exists('DISCOUNT_VAL_PROP_ID', $arCheckResult)
			&& $arCheckResult['DISCOUNT_VAL_PROP_ID']['IS_CORRECT'] == true
			&& $arFields['WEIGHT_VAL_PROP_ID'] == $arFields['DISCOUNT_VAL_PROP_ID']
		) {
			$this->addError(GetMessage('OBX_MARKET_ECOM_ERROR_3'), 3);
			return false;
		}
		return true;
	}

	protected function _onBeforeExecUpdate(&$arFields, &$arCheckResult) {
		return true;
	}

	protected function _onBeforeDelete(&$arItem) {
		CIBlockPropertyPrice::deleteByFilter(array('IBLOCK_ID' => $arItem['IBLOCK_ID']));
		return true;
	}
	protected function _onBeforeDeleteByFilter(&$arFilter, &$bCheckExistence, &$arDelete) {
		if( isset($arFilter['IBLOCK_ID'])) {
			CIBlockPropertyPrice::deleteByFilter(array('IBLOCK_ID' => $arFilter['IBLOCK_ID']));
		}
		// TODO: Дописать удаление связок св-в ИБ с ценами если VERSION = 1
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
			cb.VERSION as VERSION,
			cb.WEIGHT_VAL_PROP_ID as WEIGHT_VAL_PROP_ID,
			cb.DISCOUNT_VAL_PROP_ID as DISCOUNT_VAL_PROP_ID
		FROM
			b_iblock AS b
		LEFT JOIN obx_ecom_iblock AS cb ON (b.ID = cb.IBLOCK_ID)
		ORDER BY
			b.SORT ASC,
			b.ID ASC
SQL;
		$res = $DB->Query($sql, false, 'File: '.__FILE__."<br />\nLine: ".__LINE__);
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
		$that->deleteByFilter(array('IBLOCK_ID' => $ID));
	}

	public function registerModuleDependencies() {
		RegisterModuleDependences(
			'iblock', 'OnIBlockDelete',
			'obx.market', __CLASS__, 'onIBlockDelete', 510);
	}

	public function unRegisterModuleDependencies() {
		UnRegisterModuleDependences(
			'iblock', 'OnIBlockDelete',
			'obx.market', __CLASS__, 'onIBlockDelete');
	}
}


class ECommerceIBlock extends DBSimpleStatic {
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
