<?php
$bConnectEpilog = false;
if(!defined("BX_ROOT")) {
	$bConnectEpilog = true;
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	global $USER;
	if( !$USER->IsAdmin() ) return false;
}

DeleteDirFilesEx("/bitrix/admin/obx_market_delivery_systems.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_index.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_order_edit.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_order_props.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_order_props_edit.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_order_status.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_order_status_edit.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_orders.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_pay_systems.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_places.php");
DeleteDirFilesEx("/bitrix/admin/obx_market_statistics.php");
DeleteDirFilesEx("/bitrix/admin/ajax/obx_market_settings_catalog.php");
DeleteDirFilesEx("/bitrix/admin/ajax/obx_market_settings_currency.php");
DeleteDirFilesEx("/bitrix/admin/ajax/obx_market_settings_price.php");
DeleteDirFilesEx("/bitrix/themes/.default/obx.market");
DeleteDirFilesEx("/bitrix/themes/.default/obx.market.css");
DeleteDirFilesEx("/bitrix/components/obx.market");
DeleteDirFilesEx("/bitrix/js/obx.market");
DeleteDirFilesEx("/bitrix/tools/obx.market");
if($bConnectEpilog) require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>