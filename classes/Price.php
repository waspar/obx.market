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

/**
 * Class OBX_PriceDBS
 * @method @static OBX_PriceDBS getInstance()
 */
class PriceDBS extends DBSimple {

	const DEFAULT_PRICE_GROUP = "2";

	protected $_arTableList = array(
		'P' => 'obx_price',
		'C' => 'obx_currency',
		'F' => 'obx_currency_format'
	);
	protected $_mainTable = 'P';
	protected $_arTableLinks = array(
		0 => array(
			array("P" => "CURRENCY"),
			array("C" => "CURRENCY"),
		),
		1 => array(
			array("C" => "CURRENCY"),
			array("F" => "CURRENCY")
		),
		2 => array(
			array("P" => "CURRENCY"),
			array("F" => "CURRENCY")
		)
	);
	protected $_arTableFields = array(
		"ID"						=> array("P" => "ID"),
		"CODE"						=> array("P" => "CODE"),
		"NAME"						=> array("P" => "NAME"),
		"SORT"						=> array("P" => "SORT"),
		//"USER_GROUP"				=> array("P" => "USER_GROUP"),
		"CURRENCY"					=> array("P" => "CURRENCY"),
		"CURRENCY_COURSE"			=> array("C" => "COURSE"),
		"CURRENCY_RATE"				=> array("C" => "RATE"),
		"CURRENCY_IS_DEFAULT"		=> array("C" => "IS_DEFAULT"),
		"CURRENCY_SORT"				=> array("C" => "SORT"),
		"CURRENCY_LANG_ID"			=> array("F" => "LANGUAGE_ID"),
		"CURRENCY_NAME"				=> array("F" => "NAME"),
		"CURRENCY_FORMAT"			=> array("F" => "FORMAT"),
		"CURRENCY_THOUSANDS_SEP"	=> array("F" => "THOUSANDS_SEP"),
		"CURRENCY_DEC_PRECISION"	=> array("F" => "DEC_PRECISION")
	);
	protected $_mainTablePrimaryKey = "ID";
	protected $_mainTableAutoIncrement = "ID";
	protected $_arTableFieldsCheck = array();
	protected $_arTableUnique = array(
		"udx_obx_price" => array("CODE")
	);
	protected $_arTableFieldsDefault = array(
		"SORT" => 100
	);
	protected $_arSelectDefault = array(
		"ID", "CODE", "NAME", "SORT", "CURRENCY", "CURRENCY_NAME", "CURRENCY_LANG_ID", "CURRENCY_DEC_PRECISION"
	);
	protected $_arFilterDefault = array(
		"CURRENCY_LANG_ID" => LANGUAGE_ID,
	);
	protected $_arSortDefault = array(
		"SORT" => "ASC",
		"ID" => "ASC"
	);
	protected $_arDBSimpleLangMessages = array();

	protected $_arUserGroupsCache = array();

	function __construct() {
		$this->_arTableFieldsCheck = array(
			"ID" => self::FLD_T_INT | self::FLD_NOT_NULL,
			"CODE" => self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			"CURRENCY" => self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			"NAME" => self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			"SORT" => self::FLD_T_INT | self::FLD_DEFAULT
		);
		$this->_arDBSimpleLangMessages = array(
			"REQ_FLD_CODE" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_PRICE_ERROR_1"),
				"CODE" => 1
			),
			"REQ_FLD_CURRENCY" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_PRICE_ERROR_2"),
				"CODE" => 2
			),
			"DUP_ADD_udx_obx_price" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_PRICE_ERROR_3"),
				"CODE" => 3
			),
			"DUP_UPD_udx_obx_price" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_PRICE_ERROR_4"),
				"CODE" => 4
			),
			"NOTHING_TO_UPDATE" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_PRICE_ERROR_5"),
				"CODE" => 5
			),
			"NOTHING_TO_DELETE" => array(
				"TYPE" => "E",
				"TEXT" => GetMessage("OBX_MARKET_PRICE_ERROR_6"),
				"CODE" => 6
			)
		);
	}

	public function getByCode($CODE, $arSelect = null, $bReturnCDBResult = false) {
		$mainTable = $this->_mainTable;
		$arTableFields = $this->_arTableFields;
		if (empty($arSelect) || !is_array($arSelect)) {
			// Если SELECT пустой
			foreach ($arTableFields as $fieldCode => $arSqlField) {
				list($tlbAlias, $tblFieldName) = each($arSqlField);
				if ($tlbAlias == $mainTable) {
					$arSelect[] = $fieldCode;
				}
			}
		}
		$rsList = $this->getList(null, array("CODE" => $CODE), $arSelect, null, null, false);
		if ($bReturnCDBResult) {
			return $rsList;
		} elseif (($arElement = $rsList->Fetch())) {
			return $arElement;
		}
		return array();
	}

	public function getProductPriceList($productID, $userID = null, $langID = LANGUAGE_ID, $bWithPermissions = false) {
		global $DB;
		$productID = intval($productID);
		$rsProd = \CIBlockElement::GetByID($productID);

		if (!($arProd = $rsProd->GetNext())) {
			$this->addError(GetMessage('OBX_MARKET_PRICE_ERROR_13'), 13);
			return array();
		}
		$productID = $arProd['ID'];
		$rsLang = \CLanguage::GetByID($langID);
		if ( $arLang = $rsLang->Fetch() ) {
			$langID = $arLang['LANGUAGE_ID'];
		}
		else {
			$this->addWarning(GetMessage('OBX_MARKET_PRICE_WARNING_3'), 3);
			$langID = LANGUAGE_ID;
		}

		$arAvailPricesForUser = $this->getAvailPriceForUser($userID);

		$sqlList = <<<SQL
		SELECT
			a.PRICE_ID,
			c.NAME as PRICE_NAME,
			c.CODE as PRICE_CODE,
			c.CURRENCY as PRICE_CURRENCY,
			f.FORMAT AS CURRENCY_FORMAT,
			f.LANGUAGE_ID AS CURRENCY_LANG_ID,
			f.THOUSANDS_SEP AS CURRENCY_THOUSANDS_SEP,
			f.DEC_PRECISION AS CURRENCY_DEC_PRECISION,
			f.DEC_POINT AS CURRENCY_DEC_POINT,
			a.IBLOCK_PROP_ID AS IBLOCK_PROP_ID,
			a.IBLOCK_ID AS IBLOCK_ID,
			a.DISCOUNT_VAL_PROP_ID AS DISCOUNT_VAL_PROP_ID,
			a.WEIGHT_VAL_PROP_ID AS WEIGHT_VAL_PROP_ID
		FROM
			(SELECT  a.IBLOCK_ID, b.IBLOCK_PROP_ID, b.PRICE_ID, EC.DISCOUNT_VAL_PROP_ID as DISCOUNT_VAL_PROP_ID, EC.WEIGHT_VAL_PROP_ID as WEIGHT_VAL_PROP_ID
				FROM b_iblock_element AS a
				LEFT JOIN obx_ecom_iblock as EC ON (a.IBLOCK_ID = EC.IBLOCK_ID)
				LEFT JOIN obx_price_ibp AS b ON (a.IBLOCK_ID = b.IBLOCK_ID)
				WHERE a.ID = $productID
			) AS a
		LEFT JOIN
			obx_price AS c ON (c.ID = a.PRICE_ID)
		LEFT JOIN
			obx_currency_format as f ON (f.CURRENCY = c.CURRENCY)
		WHERE
			a.IBLOCK_PROP_ID is not null
			AND f.LANGUAGE_ID = '$langID'
		ORDER BY c.SORT ASC
SQL;
		$res = $DB->Query($sqlList, false, "File: ".__FILE__."<br />\nLine: ".__LINE__);
		$arResult = array();
		$i = 0;
		$refArOptimalPrice = null;
		while ($arPrice = $res->Fetch()) {
			$resProp = \CIBlockElement::GetProperty($arPrice["IBLOCK_ID"], $productID, array(),
				array(
					"ID" => $arPrice["IBLOCK_PROP_ID"]
				));
			$arNullResultDefault = CurrencyFormatDBS::getInstance()->getTableJoinNullFieldDefaults();
			if($arPrice['CURRENCY_FORMAT'] == null) {
				$arPrice['CURRENCY_FORMAT'] = $arNullResultDefault['FORMAT'];
			}
			if($arPrice['CURRENCY_THOUSANDS_SEP'] == null) {
				$arPrice['CURRENCY_THOUSANDS_SEP'] = $arNullResultDefault['THOUSANDS_SEP'];
			}
			if($arPrice['CURRENCY_DEC_PRECISION'] == null) {
				$arPrice['CURRENCY_DEC_PRECISION'] = $arNullResultDefault['DEC_PRECISION'];
			}
			if($arPrice['CURRENCY_DEC_POINT'] == null) {
				$arPrice['CURRENCY_DEC_POINT'] = $arNullResultDefault['DEC_POINT'];
			}
			if( $arPriceProp = $resProp->Fetch() ) {
				if( floatval($arPriceProp["VALUE"]) < 0.001) {
					$rsProduct = \CIBlockElement::GetByID($productID);
					$arProduct = $rsProduct->GetNext();
					$this->addWarning(GetMessage('OBX_MARKET_PRICE_WARNING_1', array(
						'#ID#' => $arProduct['ID'],
						'#NAME#' => $arProduct['NAME']
					)), 1);
				}
			}
			else {
				$this->addError(GetMessage('OBX_MARKET_PRICE_ERROR_15'), 15);
				return array();
			}

			$arResult[$i] = $arPrice;

			$arResult[$i]["VALUE"] = $arPriceProp["VALUE"];
			// +++ TODO : Добавить поддрежку дисконта
			$discountValue = 0;
			$arResult[$i]["DISCOUNT_VALUE"] = $discountValue;
			$arResult[$i]["TOTAL_VALUE"] = $arPriceProp["VALUE"] - $discountValue;
			// ^^^
			$arResult[$i]["AVAILABLE"] = (in_array($arPrice["PRICE_ID"], $arAvailPricesForUser)) ? "Y" : "N";
			$arResult[$i]["IS_OPTIMAL"] = 'N';

			if($arResult[$i]["AVAILABLE"] == 'Y') {
				if($refArOptimalPrice === null) {
					$refArOptimalPrice = &$arResult[$i];
					$refArOptimalPrice['IS_OPTIMAL'] = "Y";
				}
				elseif($arResult[$i]["TOTAL_VALUE"] < $refArOptimalPrice['TOTAL_VALUE']) {
					$refArOptimalPrice['IS_OPTIMAL'] = "N";
					$refArOptimalPrice = &$arResult[$i];
					$refArOptimalPrice['IS_OPTIMAL'] = "Y";
				}
			}

			$arResult[$i]["VALUE_FORMATTED"] = CurrencyFormatDBS::getInstance()->formatPrice(
				$arResult[$i]['VALUE'],
				$arResult[$i]['PRICE_CURRENCY'],	// передавая формат, тут можно и null поставить, не важно
				$langID,							// передавая формат, тут можно и null поставить, не важно
				// Сразу пишем формат, что бы метод OBX_CurrencyFormatDBS::formatPrice()
				// заново не получал эти данные из БД
				array(
					'FORMAT' => $arResult[$i]['CURRENCY_FORMAT'],
					'DEC_PRECISION' => $arResult[$i]['CURRENCY_DEC_PRECISION'],
					'DEC_POINT' => $arResult[$i]['CURRENCY_DEC_POINT'],
					'THOUSANDS_SEP' => $arResult[$i]['CURRENCY_THOUSANDS_SEP']
				)
			);
			$arResult[$i]["TOTAL_VALUE_FORMATTED"] = CurrencyFormatDBS::getInstance()->formatPrice(
				$arResult[$i]['TOTAL_VALUE'],
				$arResult[$i]['PRICE_CURRENCY'],	// передавая формат, тут можно и null поставить, не важно
				$langID,							// передавая формат, тут можно и null поставить, не важно
				// Сразу пишем формат, что бы метод OBX_CurrencyFormatDBS::formatPrice()
				// заново не получал эти данные из БД
				array(
					'FORMAT' => $arResult[$i]['CURRENCY_FORMAT'],
					'DEC_PRECISION' => $arResult[$i]['CURRENCY_DEC_PRECISION'],
					'DEC_POINT' => $arResult[$i]['CURRENCY_DEC_POINT'],
					'THOUSANDS_SEP' => $arResult[$i]['CURRENCY_THOUSANDS_SEP']
				)
			);
			$arResult[$i]["DISCOUNT_VALUE_FORMATTED"] = CurrencyFormatDBS::getInstance()->formatPrice(
				$arResult[$i]['DISCOUNT_VALUE'],
				$arResult[$i]['PRICE_CURRENCY'],
				$langID,
				array(
					'FORMAT' => $arResult[$i]['CURRENCY_FORMAT'],
					'DEC_PRECISION' => $arResult[$i]['CURRENCY_DEC_PRECISION'],
					'DEC_POINT' => $arResult[$i]['CURRENCY_DEC_POINT'],
					'THOUSANDS_SEP' => $arResult[$i]['CURRENCY_THOUSANDS_SEP']
				)
			);
			$i++;
		}
		return $arResult;
	}

	/**
	 * Получить оптимальную цену продукта
	 * @param $productID
	 * @param null $userID
	 * @param string $langID
	 * @return array
	 */
	public function getOptimalProductPrice($productID, $userID = null, $langID = LANGUAGE_ID) {
		$arPriceList = $this->getProductPriceList($productID, $userID, $langID);
		foreach($arPriceList as &$arPrice) {
			if($arPrice['IS_OPTIMAL'] == 'Y') {
				return $arPrice;
			}
		}
		return array();
	}

	/**
	 * TODO: Дописать метод
	 * @param $priceValue
	 * @param $priceCode
	 * @param null $langID
	 * @return mixed
	 */
	public function formatPrice($priceValue, $priceCode, $langID = null) {
		$format = null;
		if (!$langID) {
			$langID = LANGUAGE_ID;
		}
		if (!$format) {
			$priceID = 0;
			if (is_numeric($priceCode)) {
				$priceID = intval($priceCode);
			}
			if (!empty($this->_arFormatPriceCache[$priceCode . $priceID])) {
				$arFormat = $this->_arFormatPriceCache[$priceCode . $priceID];
			} else {
				$rsLang = \CLanguage::GetByID($langID);
				if (!$rsLang->Fetch()) {
					$this->addError(GetMessage("OBX_MARKET_PRICE_WARNING_2"), 2);
					$langID = LANGUAGE_ID;
				}
				$arPriceFilter = array("CURRENCY_LANG_ID" => $langID);
				if ($priceID) {
					$arPriceFilter["ID"] = $priceID;
				} elseif (!empty($priceCode)) {
					$arPriceFilter["CODE"] = $priceCode;
				}
				$arPriceList = $this->getListArray(null, $arPriceFilter);
				if (count($arPriceList) < 1) {
					$this->addWarning(GetMessage("OBX_MARKET_PRICE_WARNING_1"), 1);
					return $priceValue;
				}
				wd($arPriceList, __METHOD__ . '(): $arPriceList');
				$arFormat = array(
					"FORMAT" => $this->_arFormatPriceCache[$priceCode . $priceID] = $arPriceList[0]["CURRENCY_FORMAT"],
					"DEC_PRECISION" => $this->_arFormatPriceCache[$priceCode . $priceID] = $arPriceList[0]["DEC_PRECISION"],
				);
			}
		}
		return CurrencyFormat::formatPrice($priceValue, null, $langID, $arFormat);
	}

	/**
	 * @param int $priceID
	 * @param int $groupID
	 * @return int
	 */
	public function addGroup($priceID, $groupID = 0) {
		$priceID = intval($priceID);
		$groupID = intval($groupID);
		global $DB;
		$rsExistsPrice = $this->getByID($priceID, null, true);
		if( !($arExistsPrice = $rsExistsPrice->Fetch()) ) {
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_9"), array(
				"#PRICE_ID#" => $priceID
			), 9);
			return 0;
		}
		$rsExistsGroup = \CGroup::GetByID($groupID);
		if( !($arExistsGroup = $rsExistsGroup->Fetch()) ) {
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_10"), array(
				"#GROUP_ID#" => $groupID
			), 10);
			return 0;
		}
		$rsExistsRow = $DB->Query(
			'SELECT * FROM obx_price_group WHERE'
			.' PRICE_ID = \''.$arExistsPrice['ID'].'\''
			.' AND GROUP_ID = \''.$arExistsGroup['ID'].'\'');
		if($arExistsRow = $rsExistsRow->Fetch()) {
			return true;
		}
		$DB->Query("INSERT INTO obx_price_group (PRICE_ID,GROUP_ID) VALUES ('" . $priceID . "', '" . $groupID . "');");
		return true;
	}

	/**
	 * @param int $priceID
	 * @param int $groupID
	 * @return bool
	 */
	public function removeGroup($priceID, $groupID = 0) {
		$priceID = intval($priceID);
		$groupID = intval($groupID);
		global $DB;
		$res = $DB->Query("DELETE FROM obx_price_group WHERE obx_price_group.PRICE_ID ='" . $priceID . "' AND obx_price_group.GROUP_ID ='" . $groupID . "'");
		return (($res) ? true : false);
	}

	/**
	 * @param int $priceID
	 * @param Array $arGroupIDList
	 * @return bool
	 */
	public function setGroupList($priceID, $arGroupIDList) {
		$priceID = intval($priceID);

		$rsExistsPrice = $this->getByID($priceID, null, true);
		if (!$rsExistsPrice || !$rsExistsPrice->Fetch()) {
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_9"), array(
				"#PRICE_ID#" => $priceID
			), 9);
			return false;
		}

		global $DB;
		$values = "";
		if (empty($arGroupIDList) || !is_array($arGroupIDList)) {
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_11"));
			return false;
		}
		foreach ($arGroupIDList as &$groupID) {
			$groupID = intval($groupID);
		}
		$isOneIdForDelete = false;
		if (count($arGroupIDList) == 1 && $arGroupIDList[0] < 0) {
			$isOneIdForDelete = true;
		}
		if (!$isOneIdForDelete) {
			$arFilterExistsGroups = array("ID" => "");
			$arFilterExistsGroups["ID"] = implode(" | ", $arGroupIDList);
			$rsExistsGroupList = \CGroup::GetList($by = "c_sort", $order = "asc", $arFilterExistsGroups);
			$arExistGroupIDList = array();
			while ($arExistGroup = $rsExistsGroupList->Fetch()) {
				$arExistGroupIDList[] = $arExistGroup["ID"];
			}
			if (count($arExistGroupIDList) < 1) {
				$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_12"));
				return false;
			}
			$bFirst = true;
			foreach ($arExistGroupIDList as $groupID) {
				$values .= ($bFirst ? " " : ", ") . "('" . $priceID . "', '" . intval($groupID) . "')";
				$bFirst = false;
			}
		}
		$DB->StartTransaction();
		$delRes = $DB->Query("DELETE FROM obx_price_group WHERE obx_price_group.PRICE_ID = '" . $priceID . "'");
		if (!$delRes) {
			$DB->Rollback();
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_7"), array(
				"#PRICE_ID#" => $priceID
			), 7);
			return false;
		}
		if (!$isOneIdForDelete) {
			$insRes = $DB->Query("INSERT INTO obx_price_group (PRICE_ID,GROUP_ID) VALUES " . $values . "");
			if (!$insRes) {
				$DB->Rollback();
				$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_8"), array(
					"#PRICE_ID#" => $priceID
				), 8);
				return false;
			}
		}
		$DB->Commit();
		return true;
	}

	public function getGroupList($priceID, $bReturnCDBResult = false) {
		$priceID = intval($priceID);
		global $DB;

		$res = $DB->Query("SELECT GROUP_ID FROM obx_price_group WHERE PRICE_ID = '" . $priceID . "'");

		if ($bReturnCDBResult) {
			return $res;
		} else {
			$arResult = array();
			while ($arRes = $res->Fetch()) {
				$arResult[] = $arRes["GROUP_ID"];
			}
			;
			return $arResult;
		}
	}

	public function getGroupListCached($priceID) {
		if( ! array_key_exists($priceID, $this->_arUserGroupsCache) ) {
			$this->_arUserGroupsCache[$priceID] = $this->getGroupList($priceID, false);
		}
		return $this->_arUserGroupsCache[$priceID];
	}

	public function getAvailPriceForUser($userID = null, $bReturnCDBResult = false) {
		global $USER;
		if($userID === null) {
			$userID = $USER->GetID();
		}
		$userID = intval($userID);
		global $DB;

		$res = $DB->Query("SELECT DISTINCT PRICE_ID from obx_price_group where GROUP_ID in (
							SELECT GROUP_ID from b_user_group where USER_ID = '" . $userID . "')
							OR GROUP_ID = ".self::DEFAULT_PRICE_GROUP."");
		if ($bReturnCDBResult) {
			return $res;
		} else {
			$arResult = array();
			while ($arRes = $res->Fetch()) {
				$arResult[] = $arRes["PRICE_ID"];
			}
			;
			return $arResult;
		}
	}
}

/**
 * @method @static PriceDBS getInstance()
 */
class Price extends DBSimpleStatic {
	static public function getOptimalProductPrice($productID, $userID = null, $langID = LANGUAGE_ID) {
		return self::getInstance()->getOptimalProductPrice($productID, $userID, $langID);
	}
	static public function getProductPriceList($productID, $userID = null) {
		return self::getInstance()->getProductPriceList($productID, $userID);
	}
	static public function formatPrice($priceValue, $priceCode, $langID = null) {
		return self::getInstance()->formatPrice($priceValue, $priceCode, $langID);
	}
	static public function addGroup($priceID, $groupID = 0) {
		return self::getInstance()->addGroup($priceID, $groupID);
	}
	static public function removeGroup($priceID, $groupID = 0) {
		return self::getInstance()->removeGroup($priceID, $groupID);
	}
	static public function setGroupList($priceID, $arGroupIDList) {
		return self::getInstance()->setGroupList($priceID, $arGroupIDList);
	}
	static public function getGroupList($priceID, $bReturnCDBResult = false) {
		return self::getInstance()->getGroupList($priceID, $bReturnCDBResult);
	}
	static public function getGroupListCached($priceID) {
		return self::getInstance()->getGroupListCached($priceID);
	}
	static public function getAvailPriceForUser($userID, $bReturnCDBResult = false) {
		return self::getInstance()->getAvailPriceForUser($userID, $bReturnCDBResult);
	}
}
Price::__initDBSimple(PriceDBS::getInstance());
