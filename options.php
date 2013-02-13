<?php
/*********************************************
 ** @product A68:Market-Start Bitrix Module **
 ** @vendor A68 Studio                      **
 ** @mailto info@a-68.ru                    **
 *********************************************/

IncludeModuleLangFile(__FILE__);

if(!$USER->IsAdmin())return;
if(!CModule::IncludeModule("obx.market"))return;

/**
 * Закладки
 */
$arTabsList = array(
	array(
		"DIV" => "obx_market_settings_currency",
		"TAB" => GetMessage("OBX_MARKET_SETTINGS_TAB_CURRENCY"),
		"ICON" => "settings_currency",
		"TITLE" => GetMessage("OBX_MARKET_SETTINGS_TITLE_CURRENCY"),
		"CONTROLLER" => OBX_MarketSettings::getController("Currency")
	),
	array(
		"DIV" => "obx_market_settings_price",
		"TAB" => GEtMessage("OBX_MARKET_SETTINGS_TAB_PRICE"),
		"ICON" => "settings_price",
		"TITLE" => GEtMessage("OBX_MARKET_SETTINGS_TITLE_PRICE"),
		"CONTROLLER" => OBX_MarketSettings::getController("Price")
	),
	array(
		"DIV" => "obx_market_settings_catalog",
		"TAB" => GEtMessage("OBX_MARKET_SETTINGS_TAB_CATALOG"),
		"ICON" => "settings_catalog",
		"TITLE" => GEtMessage("OBX_MARKET_SETTINGS_TITLE_CATALOG"),
		"CONTROLLER" => OBX_MarketSettings::getController("Catalog")
	),
//	array(
//		"DIV" => "obx_market_settings_main",
//		"TAB" => GetMessage("MAIN_TAB_SET"),
//		"ICON" => "settings_main",
//		"TITLE" => GetMessage("MAIN_TAB_TITLE_SET")
//	),
//	array(
//		"DIV" => "obx_market_settings_access",
//		"TAB" => GEtMessage("OBX_MARKET_SETTINGS_TAB_ACCESS"),
//		"ICON" => "settings_access",
//		"TITLE" => GEtMessage("OBX_MARKET_SETTINGS_TITLE_ACCESS"),
//	)

);
$TabControl = new CAdminTabControl("tabSettings", $arTabsList);


/**
 * Шаблоны
 */

$APPLICATION->AddHeadScript("/bitrix/js/obx.market/jquery-1.8.2.min.js");
$APPLICATION->AddHeadScript("/bitrix/js/obx.market/tools.js");
$APPLICATION->AddHeadScript("/bitrix/js/obx.market/settings.js");
$APPLICATION->AddHeadScript("/bitrix/js/obx.market/jquery-cookie.js");

?>
<div id="obx_market_settings">
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">

<?
$TabControl->Begin();
foreach($arTabsList as &$arTab) {
	$TabControl->BeginNextTab();
	if( !empty($arTab["CONTROLLER"]) ) {
		$arTab["CONTROLLER"]->saveTabData();
		$arTab["CONTROLLER"]->showMessages();
		$arTab["CONTROLLER"]->showErrors();
		$arTab["CONTROLLER"]->showTabContent();
	}
}
$TabControl->End();
?>
</form>
</div>
<?
foreach($arTabsList as &$arTab) {
	if( !empty($arTab["CONTROLLER"]) ) {
		?><div id="<?=$arTab["DIV"]."_scripts"?>"><?
		$arTab["CONTROLLER"]->showTabScripts();
		?></div><?
	}
}
?>