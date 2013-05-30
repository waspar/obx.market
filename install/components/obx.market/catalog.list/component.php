<?
use OBX\Market\Price;
use OBX\Market\Basket;

/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule("obx.market")) {
	ShowError(GetMessage("OBX_MARKET_NOT_INSTALLED"));
	return false;
}
if (!CModule::IncludeModule("iblock")) {
	ShowError(GetMessage("OBX_MARKET_NOT_INSTALLED"));
	return false;
}

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["ACTION_VARIABLE"] = trim($arParams["ACTION_VARIABLE"]);
$arParams["PRODUCT_ID_VARIABLE"] = trim($arParams["PRODUCT_ID_VARIABLE"]);
$arParams["USE_QUANTITY_VARIABLE"] = $arParams["USE_QUANTITY_VARIABLE"] == "Y" ? "Y" : "N";
$arParams["QUANTITY_VARIABLE"] = trim($arParams["QUANTITY_VARIABLE"]);
$arParams["PATH_TO_BASKET"] = trim($arParams["PATH_TO_BASKET"]);

$arParams["AJAX_BUY"] = $arParams["AJAX_BUY"] == "Y" ? "Y" : "N";

/*************************************************************************
 *        Processing of the Buy link
 *************************************************************************/
$strError = "";
if (array_key_exists($arParams["ACTION_VARIABLE"], $_REQUEST) && array_key_exists($arParams["PRODUCT_ID_VARIABLE"], $_REQUEST)) {
	$Basket = Basket::getCurrent();
	$q = 1;
	if ($arParams["USE_QUANTITY_VARIABLE"] == "Y" && array_key_exists($arParams["QUANTITY_VARIABLE"], $_REQUEST)) {
		$rQ = intval($_REQUEST[$arParams["QUANTITY_VARIABLE"]]);
		$q = ($rQ > 0) ? $rQ : 1;
	}

	switch ($_REQUEST[$arParams["ACTION_VARIABLE"]]){
		case "ADD" :
			$Basket->addProduct($_REQUEST[$arParams["PRODUCT_ID_VARIABLE"]], $q);
			break;
		case "BUY" :
			$Basket->addProduct($_REQUEST[$arParams["PRODUCT_ID_VARIABLE"]], $q);
			LocalRedirect($arParams["PATH_TO_BASKET"]);
			break;
		case "DEL" :
			$Basket->removeProduct($_REQUEST[$arParams["PRODUCT_ID_VARIABLE"]]);
			break;
		default :
			break;
	}
	LocalRedirect($APPLICATION->GetCurPageParam("", array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"], $arParams["QUANTITY_VARIABLE"])));
}

if ($this->StartResultCache()) {
	$arItems = array();

	$arSelectFields = array(
		"NAME"
	, "ID"
	, "DATE_CREATE"
	, "DATE_CREATE_UNIX"
	, "IBLOCK_ID"
	, "IBLOCK_SECTION_ID"
	, "PREVIEW_PICTURE"
	, "PREVIEW_TEXT"
	, "PREVIEW_TEXT_TYPE"
	, "DETAIL_PICTURE"
	, "DETAIL_TEXT"
	, "DETAIL_TEXT_TYPE"
	, "SEARCHABLE_CONTENT"
	, "CODE"
	, "TAGS"
	, "IBLOCK_TYPE_ID"
	, "IBLOCK_CODE"
	, "IBLOCK_NAME"
	, "DETAIL_PAGE_URL"
	, "LIST_PAGE_URL"
	);
	$arOrder = array("SORT" => "ASC");
	$arFilter = array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"ACTIVE" => "Y"
	);
	$arNavStartParams = array();

	$dbItems = CIBlockElement::GetList(
		$arOrder,
		$arFilter,
		false, //mixed arGroupBy
		false, //mixed arNavStartParams
		$arSelectFields
	);
	$bPriceFound = true;
	while ($obElement = $dbItems->GetNextElement()) {

		$arItem = $obElement->GetFields();

		$arButtons = CIBlock::GetPanelButtons(
			$arItem["IBLOCK_ID"],
			$arItem["ID"],
			$arResult["ID"],
			array("SECTION_BUTTONS" => false, "SESSID" => false, "CATALOG" => true)
		);

		$arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
		$arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];

		$arItem["PREVIEW_PICTURE"] = CFile::GetFileArray($arItem["PREVIEW_PICTURE"]);
		$arItem["DETAIL_PICTURE"] = CFile::GetFileArray($arItem["DETAIL_PICTURE"]);

		/*
		 * Mapping arPrices from Bitrix:CIBlockPriceTools::GetItemPrices();
		 */
		/*
		$arItem["PRICES"] = array(
			"VALUE_NOVAT", // цена без налога
			"PRINT_VALUE_NOVAT", // цена без налога для вывода

			"VALUE_VAT", // цена с налогом
			"PRINT_VALUE_VAT", // цена с налогом для вывода

			"VATRATE_VALUE", // процент налога
			"PRINT_VATRATE_VALUE", // процент налога для вывода

			"DISCOUNT_VALUE_NOVAT", // сумма скидки без налога
			"PRINT_DISCOUNT_VALUE_NOVAT", // сумма скидки без налога для вывода

			"DISCOUNT_VALUE_VAT", // сумма скидки с налогом
			"PRINT_DISCOUNT_VALUE_VAT", // сумма скидки с налогом для вывода

			"DISCOUNT_VATRATE_VALUE", // процент налога для суммы скидки
			"PRINT_DISCOUNT_VATRATE_VALUE", // процент налога для суммы скидки для вывода

			"CURRENCY", // код валюты
			"ID", // ID ценового предложения
			"CAN_ACCESS", // возможность просмотра - Y/N
			"CAN_BUY", // возможность купить - Y/N
			"VALUE", // цена
			"PRINT_VALUE", // отформатированная цена для вывода
			"DISCOUNT_VALUE", // цена со скидкой
			"PRINT_DISCOUNT_VALUE", // отформатированная цена со скидкой
		);
		*/
		$arItem["PRICE"] = null; // Нужная цена

		$arItemPrices = Price::getProductPriceList($arItem["ID"]);
		foreach ($arItemPrices as &$arPrice) {
			if ($arPrice["IS_OPTIMAL"] == "Y" && $arPrice["AVAILABLE"] == "Y") { // IS_OPTIMAL может быть == Y только 1 раз
				$arItem["PRICE"] = $arPrice;
				$arItem["CAN_BUY"] = "Y";
			}
			$arItem["PRICES"][$arPrice["CODE"]] = array(
				"VALUE_NOVAT" => $arPrice["TOTAL_VALUE"],
				"PRINT_VALUE_NOVAT" => $arPrice["TOTAL_VALUE_FORMATTED"],

				"VALUE_VAT" => $arPrice["TOTAL_VALUE"],
				"PRINT_VALUE_VAT" => $arPrice["TOTAL_VALUE_FORMATTED"],

				"VATRATE_VALUE" => "NULL",
				"PRINT_VATRATE_VALUE" => "0%",

				"DISCOUNT_VALUE_NOVAT" => $arPrice["DISCOUNT_VALUE"],
				"PRINT_DISCOUNT_VALUE_NOVAT" => $arPrice["DISCOUNT_VALUE_FORMATTED"],

				"DISCOUNT_VALUE_VAT" => $arPrice["DISCOUNT_VALUE"],
				"PRINT_DISCOUNT_VALUE_VAT" => $arPrice["DISCOUNT_VALUE_FORMATTED"],

				"DISCOUNT_VATRATE_VALUE" => "NULL",
				"PRINT_DISCOUNT_VATRATE_VALUE" => "0%",

				"CURRENCY" => $arPrice["PRICE_CURRENCY"],
				"ID" => $arPrice["PRICE_ID"],
				"CAN_ACCESS" => $arPrice["AVAILABLE"],
				"CAN_BUY" => $arPrice["AVAILABLE"],
				"VALUE" => $arPrice["VALUE"],
				"PRINT_VALUE" => $arPrice["VALUE_FORMATTED"],
				"DISCOUNT_VALUE" => $arPrice["DISCOUNT_VALUE"],
				"PRINT_DISCOUNT_VALUE" => $arPrice["DISCOUNT_VALUE_FORMATTED"],
			);
		}
		unset($arPrice);

		$arItem["BUY_URL"] = htmlspecialchars($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"] . "=BUY&" . $arParams["PRODUCT_ID_VARIABLE"] . "=" . $arItem["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
		$arItem["ADD_URL"] = htmlspecialchars($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"] . "=ADD&" . $arParams["PRODUCT_ID_VARIABLE"] . "=" . $arItem["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));
		$arItem["DEL_URL"] = htmlspecialchars($APPLICATION->GetCurPageParam($arParams["ACTION_VARIABLE"] . "=DEL&" . $arParams["PRODUCT_ID_VARIABLE"] . "=" . $arItem["ID"], array($arParams["PRODUCT_ID_VARIABLE"], $arParams["ACTION_VARIABLE"])));


		if (empty($arItem["PRICE"])) {
			$bPriceFound = false;
		}

		$arItems[] = $arItem;
	}
	if (!$bPriceFound) {
		ShowError(GetMessage("OBX_MARKET_CMP_CAN_NOT_FIND_PRICE"));
	}
	$arResult["ITEMS"] = $arItems;

	$this->IncludeComponentTemplate();
}


