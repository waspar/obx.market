<?php
/*******************************************
 ** @product OBX:Market Bitrix Module     **
 ** @authors                              **
 **         Maksim S. Makarov aka pr0n1x  **
 **         Morozov P. Artem aka tashiro  **
 ** @license Affero GPLv3                 **
 ** @mailto rootfavell@gmail.com          **
 ** @mailto tashiro@yandex.ru             **
 ** @copyright 2013 DevTop                **
 *******************************************/

class obx_sms extends CModule
{
	var $MODULE_ID = "obx.sms";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	protected $installDir = null;
	protected $moduleDir = null;

	public function obx_sms() {
		$this->installDir = str_replace(array("\\", "//"), "/", __FILE__);
		//10 == strlen("/index.php")
		//8 == strlen("/install")
		$this->installDir = substr($this->installDir , 0, strlen($this->installDir ) - 10);
		$this->moduleDir = substr($this->installDir , 0, strlen($this->installDir ) - 8);

		$arModuleInfo = array();
		$arModuleInfo = include($this->installDir."/version.php");
		$this->MODULE_VERSION = $arModuleInfo["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleInfo["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("OBX_SMS_MODULE_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("OBX_SMS_MODULE_INSTALL_DESCRIPTION");
		$this->PARTNER_NAME = GetMessage("OBX_SMS_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("OBX_SMS_PARTNER_URI");
	}

	public function DoInstall() {
		global $APPLICATION, $step, $obModule;
		$errors = $this->InstallEvents();
		if (!is_array($errors)){
			RegisterModule($this->MODULE_ID);
		}else{
			die(GetMessage("INSTALL_ERROR")."\n"."<pre>".print_r($errors, true)."</pre>");
			return false;
		}
	}

	public function DoUninstall() {
		global $APPLICATION, $step, $obModule;
		$errors = $this->UnInstallEvents();
		if (!is_array($errors)){
			UnRegisterModule($this->MODULE_ID);
		}else{
			die(GetMessage("UNINSTALL_ERROR")."\n"."<pre>".print_r($errors, true)."</pre>");
			return false;
		}


	}

	public function InstallDB() {
		global $APPLICATION, $DB, $DBType;
		if(defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE) > 0) {
			$DB->Query("SET table_type = '".MYSQL_TABLE_TYPE."'", true);
		}
		$arErrors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/obx.sms/install/db/".$DBType."/install.sql");
		return $arErrors;
	}

	public function UnInstallDB() {
		global $APPLICATION, $DB, $DBType;
		if(defined("MYSQL_TABLE_TYPE") && strlen(MYSQL_TABLE_TYPE) > 0) {
			$DB->Query("SET table_type = '".MYSQL_TABLE_TYPE."'", true);
		}
		$arErrors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/obx.sms/install/db/".$DBType."/uninstall.sql");
		return $arErrors;
	}

	public function InstallEvents() {
		$errorPool = array();
		$_DB_ = $this->InstallDB();
		if ($_DB_ !== false){
			$errorPool[] = $_DB_;
		}
		if (count($errorPool) > 0)
			return $errorPool;
		return true;
	}
	public function UnInstallEvents() {
		$errorPool = array();
		$_DB_ = $this->UnInstallDB();
		if ($_DB_ !== false){
			$errorPool[] = $_DB_;
		}
		if (count($errorPool) > 0)
			return $errorPool;
		return true;
	}
	public function InstallFiles() { return true; }
	public function UnInstallFiles() { return true; }
	public function InstallData() { return true; }
	public function UnInstallData() { return true; }



	static public function getModuleCurDir(){
		static $strPath2Lang = null;
		if($strPath2Lang === null){
			$strPath2Lang = str_replace("\\", "/", __FILE__);
			// 18 = strlen of "/install/index.php"
			$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-18);
		}
		return $strPath2Lang;
	}

	static public function includeLangFile(){
		global $MESS;
		@include(GetLangFileName(self::getModuleCurDir()."/lang/", "/install/index.php"));
	}
}

obx_sms::includeLangFile();
