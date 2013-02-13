<?php
IncludeModuleLangFile(__FILE__);

if(!CModule::IncludeModule('iblock')){
    return false;
}
if(!CModule::IncludeModule('obx.core')) {
	$obxCorePath = $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.core/install/index.php';
	if(!file_exists($obxCorePath) ) {
		return false;
	}
	require_once $obxCorePath;
	$obxCore = new obx_core();
	$obxCore->DoInstall();
	if(!CModule::IncludeModule('obx.core')) {
		return false;
	}
}

$arModuleClasses = require dirname(__FILE__).'/classes/.classes.php';
CModule::AddAutoloadClasses('obx.market', $arModuleClasses);
?>