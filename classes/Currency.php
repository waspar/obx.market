<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

IncludeModuleLangFile(__FILE__);

class OBX_CurrencyDBS extends OBX_DBSimple {
	protected $_arTableList = array(
		'C' => 'obx_currency'
	);
	protected $_mainTable = 'C';
	protected $_arTableLinks = array();
	protected $_arTableFields = array(
		'CURRENCY'		=> array('C' => 'CURRENCY'),
		'SORT'			=> array('C' => 'SORT'),
		'COURSE'		=> array('C' => 'COURSE'),
		'RATE'			=> array('C' => 'RATE'),
		'IS_DEFAULT'	=> array('C' => 'IS_DEFAULT')
	);
	protected $_mainTablePrimaryKey = 'CURRENCY';
	protected $_mainTableAutoIncrement = null;

	protected $_arTableFieldsDefault = array(
		'SORT' => '100',
		'COURSE' => '1',
		'RATE' => '1'
	);

	protected $_bSetJustUpdatedCurrencyDefault = null;
	protected $_bSetJustCreatedCurrencyDefault = null;

	public function __construct() {
		$this->_arTableFieldsCheck = array(
			'CURRENCY'		=> self::FLD_T_NO_CHECK | self::FLD_NOT_NULL | self::FLD_NOT_ZERO,
			'SORT'			=> self::FLD_T_INT
								| self::FLD_NOT_NULL
								| self::FLD_NOT_ZERO
								| self::FLD_UNSIGNED
								| self::FLD_DEFAULT,

			'COURSE'		=> self::FLD_T_FLOAT,
			'RATE'			=> self::FLD_T_FLOAT,
			'IS_DEFAULT'	=> self::FLD_T_BCHAR,
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_CURRENCY' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_CURRENCY_ERROR_1'),
				'CODE' => 1
			),
			'DUP_PK' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_CURRENCY_ERROR_2'),
				'CODE' => 2
			),
			'NOTHING_TO_UPDATE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_CURRENCY_ERROR_3'),
				'CODE' => 3
			),
			'NOTHING_TO_DELETE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_CURRENCY_ERROR_4'),
				'CODE' => 4
			),
		);
	}

	public function __check_CURRENCY(&$value, &$arCheckResult = null) {
		if( !preg_match('~^[a-zA-Z\_][a-z0-9A-Z\_]{0,2}$~', $value) ) {
			return false;
		}
		return true;
	}

	protected function _onBeforeAdd(&$arFields) {
		// +++ automatic setDefault() in add()
		if($arFields['IS_DEFAULT'] == 'Y') {
			$arFields['IS_DEFAULT'] = 'N';
			$this->_bSetJustCreatedCurrencyDefault = $arFields['CURRENCY'];
		}
		// ^^^ automatic setDefault() in add()
		return true;
	}

	protected function _onAfterAdd(&$arFields) {
		// +++ automatic setDefault() in add()
		if($this->_bSetJustCreatedCurrencyDefault != null) {
			$this->setDefault($this->_bSetJustCreatedCurrencyDefault);
		}
		$this->_bSetJustCreatedCurrencyDefault = null;
		// ^^^ automatic setDefault() in add()
		return true;
	}

	protected function _onBeforeUpdate(&$arFields, &$arCheckResult) {
		// +++ automatic setDefault() in update()
		if( $arFields['IS_DEFAULT'] == 'Y' ) {
			$this->_bSetJustUpdatedCurrencyDefault = $arFields['CURRENCY'];
		}
		// ^^^ automatic setDefault() in update()
		return true;
	}

	protected function _onBeforeExecUpdate(&$arFields, &$arCheckResult) {
		// +++ automatic setDefault() in update()
		if($this->_bSetJustUpdatedCurrencyDefault != null && $arCheckResult['__EXIST_ROW']['IS_DEFAULT'] == 'Y') {
			$this->_bSetJustUpdatedCurrencyDefault = null;
		}
		// ^^^ automatic setDefault() in update()
		return true;
	}

	protected function _onAfterUpdate(&$arFields) {
		// +++ automatic setDefault() in update()
		if($this->_bSetJustUpdatedCurrencyDefault != null) {
			$this->setDefault($this->_bSetJustUpdatedCurrencyDefault);
		}
			// Clear currency info cache
			OBX_CurrencyInfo::clearInstance($this->_bSetJustUpdatedCurrencyDefault);
		$this->_bSetJustUpdatedCurrencyDefault = null;
		// ^^^ automatic setDefault() in update()
		return true;
	}


	public function setDefault($currency) {
		global $DB;
		if( !preg_match('~^[a-zA-Z\_][a-z0-9A-Z\_]{0,2}$~', $currency) ) {
			return false;
		}
		$rsExists = $DB->Query(
			'SELECT `CURRENCY`, `IS_DEFAULT` FROM `'.$this->_arTableList['C'].'`'
				.' WHERE `CURRENCY`=\''.$currency.'\'',
			false, 'File: '.__FILE__."<br />\nLine: ".__LINE__);
		if( ! ($arExists = $rsExists->Fetch()) ) {
			return false;
		}
		$DB->Query('UPDATE `'.$this->_arTableList['C'].'` SET IS_DEFAULT=\'N\' WHERE IS_DEFAULT=\'Y\'');
		$DB->Query('UPDATE `'.$this->_arTableList['C'].'` SET IS_DEFAULT=\'Y\' WHERE `CURRENCY`=\''.$arExists['CURRENCY'].'\'');
		return true;
	}

	public function getDefault() {
		$arDefault = $this->getDefaultArray();
		if( array_key_exists('IS_DEFAULT', $arDefault) ) {
			return $arDefault['CURRENCY'];
		}
		else {
			return null;
		}
	}
	public function getDefaultArray() {
		global $DB;
		$rsDefault = parent::getList(null, array(
			'IS_DEFAULT' => 'Y'
		));
		if( ($arDefault = $rsDefault->Fetch()) ) {
			return $arDefault;
		}
		$rsDefault = parent::getList(array('SORT' => 'ASC','ID' => 'ASC'));
		if( ($arDefault = $rsDefault->Fetch()) ) {
			$bSuccess = $this->setDefault($arDefault['CURRENCY']);
			if($bSuccess) {
				$arDefault['IS_DEFAILT'] = 'Y';
				return $arDefault;
			}
			else {
				return array();
			}
		}
		$this->addError(GetMessage('OBX_MARKET_CURRENCY_ERROR_5'), 5);
		return array();
	}
}
class OBX_Currency extends OBX_DBSimpleStatic {

	static public function setDefault($currency, &$bIsAlreadyDefault = false) {
		return self::getInstance()->setDefault($currency, $bIsAlreadyDefault);
	}
	static public function getDefault() {
		return self::getInstance()->getDefault();
	}
	static public function getDefaultArray() {
		return self::getInstance()->getDefaultArray();
	}
}
OBX_Currency::__initDBSimple(OBX_CurrencyDBS::getInstance());


//class OBX_CurrencyDBS_BAK extends OBX_DBSimple
//{
//	protected $_arTableList = array(
//		"C" => "obx_currency"
//	);
//	protected $_mainTable = 'C';
//	protected $_arTableLinks = array();
//	protected $_arTableFields = array(
//		"CURRENCY"		=> array("C" => "CURRENCY"),
//		"SORT"			=> array("C" => "SORT"),
//		"COURSE"		=> array("C" => "COURSE"),
//		"RATE"			=> array("C" => "RATE"),
//		"IS_DEFAULT"	=> array("C" => "IS_DEFAULT")
//	);
//	protected $_mainTablePrimaryKey = "CURRENCY";
//	protected $_mainTableAutoIncrement = null;
//	protected $_arTableFieldsDefault = array(
//		"SORT" => "100",
//		"COURSE" => "1",
//		"RATE" => "1"
//	);
//	protected $_arDBSimpleLangMessages = array();
//	protected $_arTableFieldsCheck = array();
//	public function __construct() {
//		$this->_arTableFieldsCheck = array(
//			"CURRENCY"		=> self::FLD_T_CODE | self::FLD_NOT_NULL,
//			"SORT"			=> self::FLD_T_INT | self::FLD_DEFAULT,
//			"COURSE"		=> self::FLD_T_FLOAT,
//			"RATE"			=> self::FLD_T_FLOAT,
//			"IS_DEFAULT"	=> self::FLD_T_BCHAR,
//		);
//		$this->_arDBSimpleLangMessages = array(
//			"DUP_PK" => array(
//				"TYPE" => "E",
//				"TEXT" => GetMessage("OBX_MARKET_CURRENCY_ERROR_2"),
//				"CODE" => 2
//			)
//		);
//	}
//
//	/**
//	 * Переопределяет метод OBX_DBSimple::getList более простой реализацией
//	 * @override
//	 * @param null $currency
//	 * @return bool|CDBResult
//	 */
//	public function getList($currency = null) {
//		global $DB;
//		$strSql = 'SELECT * FROM `'.$this->_arTableList["C"].'` ';
//
//		if($currency) {
//			$currency = trim($currency);
//			if( preg_match('~^[a-zA-Z][a-zA-Z0-9]{0,3}$~', $currency) ) {
//				$strSql .= ' WHERE `CURRENCY` = \''.$currency.'\'';
//			}
//		}
//		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		return $res;
//	}
//
//	/**
//	 * Функция возвращает список-массив Валют
//	 * По сути обертка над $this->getList(...)
//	 * @param String $code Код Валюты
//	 * @param String $langID Идентификатор языка
//	 * @return Array
//	 */
//	public function getListArray($code = null) {
//		if( ($res = $this->getList($code)) ) {
//			$arCurrencyList = array();
//			while($arCurrency = $res->Fetch()) {
//				$arCurrencyList[] = $arCurrency;
//			}
//			return $arCurrencyList;
//		}
//		return array();
//	}
//
//	public function getByID($CURRENCY, $bReturnCDBResult = false) {
//		$rsList = $this->getList(($CURRENCY));
//		if(!$bReturnCDBResult) {
//			if( ($arPrice = $rsList->Fetch()) ) {
//				return $arPrice;
//			}
//			return array();
//		}
//		return $rsList;
//	}
//
//	public function add($arFields) {
//		global $DB;
//		$this->prepareFieldsData(self::PREPARE_ADD, $arFields);
//		if( !isset($arFields["CURRENCY"]) ) {
//			$this->addError(GetMessage("OBX_MARKET_CURRENCY_ERROR_1"), 1);
//			return false;
//		}
//
//		// проверка на уникальность
//		$strSelectSql = 'SELECT * FROM `'.$this->_arTableList["C"].'`'
//			."\n".' WHERE `CURRENCY` = \''.$arFields["CURRENCY"].'\''
//		.';';
//		$rsSelect = $DB->Query($strSelectSql, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		if($rsSelect->Fetch()) {
//			$this->addError(GetMessage("OBX_MARKET_CURRENCY_ERROR_2", array(
//				"#CURRENCY#" => $arFields["CURRENCY"]
//			)), 2);
//			return false;
//		}
//		// Пока не даем сделать валюту по дефолту
//		// TODO: Написать логику для установки валюты по дефолту
//		$arFields["IS_DEFAULT"] = "N";
//		//$arColumns = $DB->GetTableFields($this->_arTableList["C"]);
//		$arInsert = $DB->PrepareInsert($this->_arTableList["C"], $arFields);
//		$strSql = 'INSERT INTO '.$this->_arTableList["C"].' ('.$arInsert[0].') VALUES('.$arInsert[1].')';
//		//d($strSql, '$strSql');
//		$DB->Query($strSql, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		return true;
//	}
//
//
//	public function update($arFields) {
//		global $DB;
//		$this->prepareFieldsData(self::PREPARE_UPDATE, $arFields);
//		$strSelectSql = 'SELECT * FROM `'.$this->_arTableList["C"].'` WHERE (1=1)';
//		if( isset($arFields["CURRENCY"]) ) {
//			$strSelectSql .= ' AND `CURRENCY` = \''.$arFields["CURRENCY"].'\'';
//		}
//		else {
//			$this->addError(GetMessage("OBX_MARKET_CURRENCY_ERROR_3"), 3);
//			return false;
//		}
//
//		$rsExists = $DB->Query($strSelectSql, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		if( !$arExists = $rsExists->Fetch() ) {
//			$this->addError(GetMessage("OBX_MARKET_CURRENCY_ERROR_3"), 3);
//			return false;
//		}
//		$bSetDefault = false;
//		if($arFields["IS_DEFAULT"] == 'Y') {
//			$bSetDefault = true;
//		}
//		$arFields["IS_DEFAULT"] = $arExists["IS_DEFAULT"];
//		$strUpdate = $DB->PrepareUpdate($this->_arTableList["C"], $arFields);
//		$strUpdate = 'UPDATE `'.$this->_arTableList["C"].'` SET '.$strUpdate.' WHERE `CURRENCY` = \''.$DB->ForSql($arExists["CURRENCY"]).'\';';
//		//d($strUpdate, '$strUpdate');
//		$DB->Query($strUpdate, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		if($bSetDefault) {
//			$this->setDefault($arExists["CURRENCY"]);
//		}
//		return true;
//	}
//
//	public function delete($currency) {
//		global $DB;
//		$rsSelect = $this->getList($currency);
//		if( ! $arRes = $rsSelect->Fetch() ) {
//			$this->addError(GetMessage("OBX_MARKET_CURRENCY_ERROR_4", array(
//				"#CURRENCY#" => htmlspecialcharsEx($currency),
//			)), 4);
//			return false;
//		}
//		$DB->Query('DELETE FROM `'.$this->_arTableList["C"].'` WHERE `CURRENCY` = \''.$arRes["CURRENCY"].'\';', false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		return true;
//	}
//
//	public function setDefault($currency, &$bIsAlreadyDefault = false) {
//		global $DB;
//		if( !preg_match('~^[a-zA-Z\_][a-z0-9A-Z\_]{0,2}$~', $currency) ) {
//			return false;
//		}
//		$bIsAlreadyDefault = false;
//		$rsExists = $DB->Query(
//				'SELECT `CURRENCY`, `IS_DEFAULT` FROM `'.$this->_arTableList["C"].'`'
//				.' WHERE `CURRENCY`=\''.$currency.'\'',
//			false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
//		if( ($arExists = $rsExists->Fetch()) ) {
//			if( $arExists["IS_DEFAULT"] == "Y" ) {
//				$bIsAlreadyDefault = true;
//				return true;
//			}
//		}
//		else {
//			return false;
//		}
//		$rsDefault = parent::getList(null, array(
//			"IS_DEFAULT" => "Y"
//		));
//		if( ($arDefault = $rsDefault->Fetch()) ) {
//			if( $arDefault["CURRENCY"] != $currency ) {
//				$DB->Query('UPDATE `'.$this->_arTableList["C"].'` SET IS_DEFAULT=\'N\' WHERE IS_DEFAULT=\'Y\'');
//			}
//		}
//		$DB->Query('UPDATE `'.$this->_arTableList["C"].'` SET IS_DEFAULT=\'Y\' WHERE `CURRENCY`=\''.$arExists["CURRENCY"].'\'');
//		return true;
//	}
//	public function getDefault() {
//		$arDefault = $this->getDefaultArray();
//		if( array_key_exists('IS_DEFAULT', $arDefault) ) {
//			return $arDefault['CURRENCY'];
//		}
//		else {
//			return null;
//		}
//	}
//	public function getDefaultArray() {
//		global $DB;
//		$rsDefault = parent::getList(null, array(
//			"IS_DEFAULT" => "Y"
//		));
//		if( ($arDefault = $rsDefault->Fetch()) ) {
//			return $arDefault;
//		}
//		$rsDefault = parent::getList(array("SORT" => "ASC","ID" => "ASC"));
//		if( ($arDefault = $rsDefault->Fetch()) ) {
//			$bSuccess = $this->setDefault($arDefault["CURRENCY"]);
//			if($bSuccess) {
//				$arDefault['IS_DEFAILT'] = 'Y';
//				return $arDefault;
//			}
//			else {
//				return array();
//			}
//		}
//		$this->addError(GetMessage("OBX_MARKET_CURRENCY_ERROR_5"),5);
//		return array();
//	}
//}
