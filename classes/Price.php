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

IncludeModuleLangFile(__FILE__);

class OBX_PriceDBS extends OBX_DBSimple {

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
		"ID" => array("P" => "ID"),
		"CODE" => array("P" => "CODE"),
		"NAME" => array("P" => "NAME"),
		"SORT" => array("P" => "SORT"),
//		"USER_GROUP"				=> array("P"	=> "USER_GROUP"),
		"CURRENCY" => array("P" => "CURRENCY"),
		"CURRENCY_COURSE" => array("C" => "COURSE"),
		"CURRENCY_RATE" => array("C" => "RATE"),
		"CURRENCY_IS_DEFAULT" => array("C" => "IS_DEFAULT"),
		"CURRENCY_SORT" => array("C" => "SORT"),
		"CURRENCY_LANG_ID" => array("F" => "LANGUAGE_ID"),
		"CURRENCY_NAME" => array("F" => "NAME"),
		"CURRENCY_FORMAT" => array("F" => "FORMAT"),
		"CURRENCY_THOUSANDS_SEP" => array("F" => "THOUSANDS_SEP"),
		"CURRENCY_DEC_PRECISION" => array("F" => "DEC_PRECISION")
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

	public function getProductPriceList($productID, $userID = null, $langID = LANGUAGE_ID) {
		$productID = intval($productID);
		$prodRes = CIBlockElement::GetByID($productID);

		if (!$prodRes) {
			$this->addError(GetMessage('OBX_MARKET_PRICE_ERROR_13'), 13);
			return array();
		}

		global $DB;
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
			a.IBLOCK_PROP_ID,
			a.IBLOCK_ID
		FROM
			(SELECT  a.IBLOCK_ID, b.IBLOCK_PROP_ID, b.PRICE_ID
				FROM b_iblock_element AS a
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
		$larOptimalPrice = null;
		while ($arPrice = $res->Fetch()) {
			$resProp = CIBlockElement::GetProperty($arPrice["IBLOCK_ID"], $productID, array(),
				array(
					"ID" => $arPrice["IBLOCK_PROP_ID"]
				));
			if( $arPriceProp = $resProp->Fetch() ) {
				if( floatval($arPriceProp["VALUE"]) < 0.001) {
					$rsProduct = CIBlockElement::GetByID($productID);
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
			// TODO : Добавить поддрежку дисконта
			$discountValue = 0;
			$arResult[$i]["DISCONT_VALUE"] = $discountValue;
			$arResult[$i]["TOTAL_VALUE"] = $arPriceProp["VALUE"] - $discountValue;
			// ^^^
			$arResult[$i]["AVAILABLE"] = (in_array($arPrice["PRICE_ID"], $arAvailPricesForUser)) ? "Y" : "N";

			if($arResult[$i]["AVAILABLE"] == 'Y') {
				if($larOptimalPrice === null) {
					$larOptimalPrice = &$arResult[$i];
					$larOptimalPrice['IS_OPTIMAL'] = "Y";
				}
				elseif($arResult[$i]["TOTAL_VALUE"] < $larOptimalPrice['TOTAL_VALUE']) {
					$larOptimalPrice['IS_OPTIMAL'] = "N";
					$larOptimalPrice = &$arResult[$i];
					$larOptimalPrice['IS_OPTIMAL'] = "Y";
				}
			}

			// TODO : Добавить форматирование цены
			$arResult[$i]["VALUE_FORMATTED"] = "__VALUE FORMATTED";
			$i++;
		}
		return $arResult;
	}

	public function getOptimalProductPrice($productID, $userID = null, $langID = LANGUAGE_ID) {
		$arPriceList = $this->getProductPriceList($productID, $userID, $langID);
		foreach($arPriceList as &$arPrice) {
			if($arPrice['IS_OPTIMAL'] == 'Y') {
				return $arPrice;
			}
		}
		return array();
	}

	public function _getValue(&$arElement, &$arPrice, $bApplyFormat = false) {

	}

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
				$rsLang = CLanguage::GetByID($langID);
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
		return OBX_CurrencyFormat::formatPrice($priceValue, null, $langID, $arFormat);
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
		$rsExistsPrice = $this->getByID($priceID);
		if (!$rsExistsPrice->Fetch()) {
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_9"), array(
				"#PRICE_ID#" => $priceID
			), 9);
			return 0;
		}
		$rsExistsGroup = CGroup::GetByID($groupID);
		if (!$rsExistsGroup->Fetch()) {
			$this->addError(GetMessage("OBX_MARKET_PRICE_ERROR_10"), array(
				"#GROUP_ID#" => $groupID
			), 10);
			return 0;
		}
		$DB->Query("INSERT INTO obx_price_group (PRICE_ID,GROUP_ID) VALUES ('" . $priceID . "', '" . $groupID . "');");
		return $DB->LastID();
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
			$rsExistsGroupList = CGroup::GetList($by = "c_sort", $order = "asc", $arFilterExistsGroups);
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
 * @method OBX_PriceDBS getInstance()
 */
class OBX_Price extends OBX_DBSimpleStatic {

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
	static public function getAvailPriceForUser($userID, $bReturnCDBResult = false) {
		return self::getInstance()->getAvailPriceForUser($userID, $bReturnCDBResult);
	}
}
OBX_Price::__initDBSimple(OBX_PriceDBS::getInstance());
?>