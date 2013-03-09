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
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_delivery_systems.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_index.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_order_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_order_props.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_order_props_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_order_status.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_order_status_edit.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_orders.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_pay_systems.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_places.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/obx_market_statistics.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax/obx_market_settings_catalog.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ajax/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax/obx_market_settings_currency.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ajax/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/admin/ajax/obx_market_settings_price.php", $_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/ajax/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/themes/.default/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/themes/.default/obx.market.css", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/components/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/js/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js/", true, true);
OBX_CopyDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/obx.market/install/tools/obx.market", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/", true, true);
if($bConnectEpilog) require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>