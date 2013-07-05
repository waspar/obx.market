<?php
/***********************************************
 ** @product OBX:SMS Bitrix Module            **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 **         Morozov P. Artem aka tashiro      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Sms\Provider\Provider;

if (!CModule::IncludeModule("iblock")) {
	return false;
}

global $DB, $APPLICATION, $MESS, $DBType;

IncludeModuleLangFile(__FILE__);

if (!IsModuleInstalled("obx.core")) {
	$obx_core_path = $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/obx.core/install/index.php";
	if (!file_exists($obx_core_path)) {
		//$APPLICATION->ThrowException(GetMessage("OBX_SMS_OBX_CORE_NOT_INSTALLED"));
		ShowError(GetMessage("OBX_SMS_OBX_CORE_NOT_INSTALLED"));
		return false;
	}
	require_once($obx_core_path);
	$obx_core = new obx_core();
	$obx_core->DoInstall();
}
if (!CModule::IncludeModule("obx.core")) {
	$APPLICATION->ThrowException(GetMessage("OBX_SMS_OBX_CORE_NOT_INSTALLED"));
	return false;
}
CModule::IncludeModule('obx.core');
$arModuleClasses = require dirname(__FILE__).'/classes/.classes.php';
CModule::AddAutoloadClasses("obx.sms", $arModuleClasses);
/*
 * Регистрация всех провайдеров в папке ./providers/
 */
Provider::includeProviders();