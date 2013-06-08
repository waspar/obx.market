<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/


require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
if(!CModule::IncludeModule("obx.market"))return;

// Доступ
//if (!$USER->CanDoOperation('edit_orders'))
//	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
//$isAdmin = $USER->CanDoOperation('edit_orders');
if (!$USER->IsAdmin()) {
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

echo "Редактирование систем доставки";

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>