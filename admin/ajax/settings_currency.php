<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

CModule::IncludeModule("obx.market");

$TabContentController = OBX_MarketSettings::getController("Currency");

if( !empty($_REQUEST["obx_currency_update"])
	|| !empty($_REQUEST["obx_currency_new"])
	|| !empty($_REQUEST["obx_currency_delete"])
) {
	$TabContentController->saveTabData();
	$TabContentController->showErrors();
	$TabContentController->showWarnings();
	$TabContentController->showMessages();
}
$TabContentController->showTabContent();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");
?>