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
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if( !CModule::IncludeModule('obx.market') ) {
	ShowError(GetMessage('OBX_MARKET_NOT_INSTALLED'));
	return false;
}
if( !CModule::IncludeModule("iblock") ) {
	ShowError(GetMessage('OBX_MARKET_NOT_INSTALLED'));
	return false;
}

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);

if ($this->StartResultCache())
{
	$arItems = array();

	$arSelectFields = array("*");
	$arOrder = array("SORT"=>"ASC");
	$arFilter = array(
		"IBLOCK_TYPE" 	=> $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" 	=> $arParams["IBLOCK_ID"],
		"ACTIVE" 		=> "Y"
	);
	$arNavStartParams = array();

	$dbItems = CIBlockElement::GetList(
		$arOrder,
		$arFilter,
		false, //mixed arGroupBy
		false, //mixed arNavStartParams
		$arSelectFields
 );
	while ($arItem = $dbItems->GetNext()) {
		$arItems[] = $arItem;
	}
	$arResult["ITEMS"] = $arItems;

	$this->IncludeComponentTemplate();
}


