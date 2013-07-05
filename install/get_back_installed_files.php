<?php
$bConnectEpilog = false;
if(!defined("BX_ROOT")) {
	$bConnectEpilog = true;
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	global $USER;
	if( !$USER->IsAdmin() ) return false;
}

if(!function_exists("OBX_CopyDirFilesEx")) {
	function OBX_CopyDirFilesEx($path_from, $path_to, $ReWrite = True, $Recursive = False, $bDeleteAfterCopy = False, $strExclude = "") {
		$path_from = str_replace(array("\\", "//"), "/", $path_from);
		$path_to = str_replace(array("\\", "//"), "/", $path_to);
		if(is_file($path_from) && !is_file($path_to)) {
			if( CheckDirPath($path_to) ) {
				$file_name = substr($path_from, strrpos($path_from, "/")+1);
				$path_to .= $file_name;
				return CopyDirFiles($path_from, $path_to, $ReWrite, $Recursive, $bDeleteAfterCopy, $strExclude);
			}
		}
		if( is_dir($path_from) && substr($path_to, strlen($path_to)-1) == "/" ) {
			$folderName = substr($path_from, strrpos($path_from, "/")+1);
			$path_to .= $folderName;
		}
		return CopyDirFiles($path_from, $path_to, $ReWrite, $Recursive, $bDeleteAfterCopy, $strExclude);
	}
}
if( is_file($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.core/install/get_back_installed_files.php") ) {
	require_once $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.core/install/get_back_installed_files.php";
}
DeleteDirFilesEx("/bitrix/modules/obx.market/install/modules/obx.core");
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.core", $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.market/install/modules/", true, true, FALSE, "modules");
if( is_file($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.sms/install/get_back_installed_files.php") ) {
	require_once $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.sms/install/get_back_installed_files.php";
}
DeleteDirFilesEx("/bitrix/modules/obx.market/install/modules/obx.sms");
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.sms", $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.market/install/modules/", true, true, FALSE, "modules");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_delivery_systems.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_index.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_order_edit.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_order_props.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_order_props_edit.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_order_status.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_order_status_edit.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_orders.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_pay_systems.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_places.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_product_search.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/obx_market_statistics.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/ajax/obx_market_settings_catalog.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/ajax/obx_market_settings_currency.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/admin/ajax/obx_market_settings_price.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/themes/.default/obx.market");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/themes/.default/obx.market.css");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/components/obx.market");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/php_interface/event.d/obx.market.mysql_group_concat_max_len.php");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/js/obx.market");
DeleteDirFilesEx("/bitrix/modules/obx.market/install/tools/obx.market");
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_delivery_systems.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_index.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_order_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_order_props.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_order_props_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_order_status.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_order_status_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_orders.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_pay_systems.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_places.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_product_search.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/obx_market_statistics.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/", true, true);
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ajax/obx_market_settings_catalog.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ajax/obx_market_settings_currency.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ajax/obx_market_settings_price.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax/", true, true);
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/themes/.default") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/themes/.default", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/themes/.default/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default/obx.market.css", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/themes/.default/", true, true);
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/components") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/components", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/components/", true, true);
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/php_interface/event.d") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/php_interface/event.d", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/event.d/obx.market.mysql_group_concat_max_len.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/php_interface/event.d/", true, true);
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/js") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/js", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/js/", true, true);
if( ! is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/tools") ) {
	@mkdir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/tools", BX_DIR_PERMISSIONS, true);
}
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/tools/", true, true);
if($bConnectEpilog) require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>