<?
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
	return;
}

$dbIBlockType = CIBlockType::GetList(
	array("sort" => "asc"),
	array("ACTIVE" => "Y")
);
while ($arIBlockType = $dbIBlockType->Fetch()) {
	if ($arIBlockTypeLang = CIBlockType::GetByIDLang($arIBlockType["ID"], LANGUAGE_ID))
		$arIblockType[$arIBlockType["ID"]] = "[" . $arIBlockType["ID"] . "] " . $arIBlockTypeLang["NAME"];
}

$arIblockId = array();
$dbIblockId = CIBlock::GetList(array(), array("IBLOCK_TYPE" => $arCurrentValues["IBLOCK_TYPE"]));
while ($arIblock = $dbIblockId->Fetch()) {
	$arIblockId[$arIblock["ID"]] = $arIblock["NAME"];
}

$arComponentParameters = array(
	"GROUPS" => array(
		"PARAMS" => array(
			"NAME" => GetMessage("DVT_PARAMS")
		),
	),
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("DVT_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIblockType,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("DVT_IBLOCK_ID"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "Y",
			"VALUES" => $arIblockId,
		),
		"ACTION_VARIABLE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("DVT_ACTION_VARIABLE"),
			"TYPE" => "STRING",
			"DEFAULT" => "action"
		),
		"PRODUCT_ID_VARIABLE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("DVT_PRODUCT_ID_VARIABLE"),
			"TYPE" => "STRING",
			"DEFAULT" => "prodId"
		),
		"USE_QUANTITY_VARIABLE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("DVT_USE_QUANTITY_VARIABLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y"
		),
		"QUANTITY_VARIABLE" => array(),
		"PATH_TO_BASKET" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("DVT_PATH_TO_BASKET"),
			"TYPE" => "STRING",
			"DEFAULT" => "/personal/cart/",
		),

		"SET_TITLE" => array(),
		"CACHE_TYPE" => array(),
		"CACHE_TIME" => array(),
	)
);

if (!empty($arCurrentValues["IBLOCK_TYPE"])) {
	$arIblockId = array();
	$dbIblockId = CIBlock::GetList(array(), array("TYPE" => $arCurrentValues["IBLOCK_TYPE"]));
	while ($arIblock = $dbIblockId->Fetch()) {
		$arIblockId[$arIblock["ID"]] = $arIblock["NAME"];
	}
	$arComponentParameters["PARAMETERS"]["IBLOCK_ID"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("DVT_IBLOCK_ID"),
		"TYPE" => "LIST",
		"ADDITIONAL_VALUES" => "Y",
		"VALUES" => $arIblockId,
	);
}
if ($arCurrentValues["USE_QUANTITY_VARIABLE"] == "Y") {
	$arComponentParameters["PARAMETERS"]["QUANTITY_VARIABLE"] = array(
		"PARENT" => "BASE",
		"NAME" => GetMessage("DVT_QUANTITY_VARIABLE"),
		"TYPE" => "STRING",
		"DEFAULT" => "q"
	);
}