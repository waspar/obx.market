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

$TabContentController = OBX\Market\Settings::getController("Catalog");

if( !empty($_REQUEST["obx_iblock_is_ecom"])
	|| !empty($_REQUEST["obx_ib_price_prop"])
) {
	$TabContentController->saveTabData();
	$TabContentController->showErrors();
	$TabContentController->showWarnings();
	$TabContentController->showMessages();
}
$TabContentController->showTabContent();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_after.php");
?>