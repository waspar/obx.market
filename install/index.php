<?
/*************************************************
 ** @product OBX:Market Bitrix Module Installer **
 ** @License EULA                               **
 ** @copyright 2013 DevTop                      **
 *************************************************/

use OBX\Market\Currency;
use OBX\Market\CurrencyFormat;
use OBX\Market\Price;
use OBX\Market\ECommerceIBlock;
use OBX\Market\CIBlockPropertyPrice;
use OBX\Market\OrderStatus;
use OBX\Market\OrderProperty;
use OBX\Market\OrderPropertyEnum;

class obx_market extends CModule {
	var $MODULE_ID = "obx.market";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	protected $installDir = null;
	protected $moduleDir = null;
	protected $bxModulesDir = null;
	protected $arErrors = array();

	public function obx_market() {
		self::includeLangFile();
		$this->installDir = str_replace(array("\\", "//"), "/", __FILE__);
		//10 == strlen("/index.php")
		//8 == strlen("/install")
		$this->installDir = substr($this->installDir, 0, strlen($this->installDir) - 10);
		$this->moduleDir = substr($this->installDir, 0, strlen($this->installDir) - 8);
		$this->bxModulesDir = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules";

		$arModuleInfo = include($this->installDir . "/version.php");
		$this->MODULE_VERSION = $arModuleInfo["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleInfo["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("OBX_MODULE_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("OBX_MODULE_INSTALL_DESCRIPTION");
		$this->PARTNER_NAME = GetMessage("OBX_PARTNER_NAME");
		$this->PARTNER_URI = GetMessage("OBX_PARTNER_URI");
	}

	public function DoInstall() {
		$bSuccess = true;
		$bSuccess = $this->InstallDB() && $bSuccess;
		$bSuccess = $this->InstallFiles() && $bSuccess;
		$bSuccess = $this->InstallDeps() && $bSuccess;
		$bSuccess = $this->InstallEvents() && $bSuccess;
		$bSuccess = $this->InstallTasks() && $bSuccess;
		if($bSuccess) {
			if( !IsModuleInstalled($this->MODULE_ID) ) {
				RegisterModule($this->MODULE_ID);
			}
			$this->InstallData();
		}
		return $bSuccess;
	}
	public function DoUninstall() {
		$bSuccess = true;
		$bSuccess = $this->UnInstallTasks() && $bSuccess;
		$bSuccess = $this->UnInstallEvents() && $bSuccess;
		//$bSuccess = $this->UnInstallDeps() && $bSuccess;
		$bSuccess = $this->UnInstallFiles() && $bSuccess;
		$bSuccess = $this->UnInstallDB() && $bSuccess;		
		if($bSuccess) {
			if( IsModuleInstalled($this->MODULE_ID) ) {
				UnRegisterModule($this->MODULE_ID);
			}
		}
		return $bSuccess;
	}
	public function InstallFiles() {
		if (is_file($this->installDir . "/install_files.php")) {
			require($this->installDir . "/install_files.php");
		}
		return true;
	}
	public function UnInstallFiles() {
		if (is_file($this->installDir . "/uninstall_files.php")) {
			require($this->installDir . "/uninstall_files.php");
		}
		return true;
	}

	public function InstallDB() {
		global $DB, $DBType;
		if( is_file($this->installDir.'/db/'.$DBType.'/install.sql') ) {
			$this->prepareDBConnection();
			$arErrors = $DB->RunSQLBatch($this->installDir.'/db/'.$DBType.'/install.sql');
			if( is_array($arErrors) && count($arErrors)>0 ) {
				$this->arErrors = $arErrors;
				return false;
			}
		}
		return true;
	}
	public function UnInstallDB() {
		global $DB, $DBType;
		if( is_file($this->installDir.'/db/'.$DBType.'/uninstall.sql') ) {
			$this->prepareDBConnection();
			$arErrors = $DB->RunSQLBatch($this->installDir.'/db/'.$DBType.'/uninstall.sql');
			if( is_array($arErrors) && count($arErrors)>0 ) {
				$this->arErrors = $arErrors;
				return false;
			}
		}
		return true;
	}
	
	public function InstallEvents() {
		RegisterModuleDependences("main", "OnBuildGlobalMenu", "obx.market", "OBX_Market_BXMainEventsHandlers", "OnbBuildGlobalMenu");
		require_once $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/" . $this->MODULE_ID . "/include_static.php";
		ECommerceIBlock::registerModuleDependencies();
		CIBlockPropertyPrice::registerModuleDependencies();
		return true;
	}

	public function UnInstallEvents() {
		UnRegisterModuleDependences("main", "OnBuildGlobalMenu", "obx.market", "OBX_Market_BXMainEventsHandlers", "OnbBuildGlobalMenu");
		require_once $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/" . $this->MODULE_ID . "/include_static.php";
		ECommerceIBlock::unRegisterModuleDependencies();
		CIBlockPropertyPrice::unRegisterModuleDependencies();
		return true;
	}
	
	public function InstallTasks() { return true; }
	public function UnInstallTasks() { return true; }

	public function InstallDeps() {
		if( is_file($this->installDir."/install_deps.php") ) {
			require $this->installDir."/install_deps.php";
			$arDepsList = $this->getDepsList();
			foreach($arDepsList as $depModID => $depModClass) {
				$depModInstallerFile = $this->bxModulesDir."/".$depModID."/install/index.php";
				if( is_file($depModInstallerFile) ) {
					require_once $depModInstallerFile;
					/** @var CModule $DepModInstaller */
					$bSuccess = true;
					$DepModInstaller = new $depModClass;
					$bSuccess = $DepModInstaller->InstallDB() && $bSuccess;
					$bSuccess = $DepModInstaller->InstallEvents() && $bSuccess;
					$bSuccess = $DepModInstaller->InstallTasks() && $bSuccess;
					if( method_exists($DepModInstaller, 'InstallData') ) {
						$bSuccess = $DepModInstaller->InstallData() && $bSuccess;
					}
					if( $bSuccess ) {
						if( !IsModuleInstalled($depModID) ) {
							RegisterModule($depModID);
						}
					}
				}
			}
		}
		return true;
	}
	public function UnInstallDeps() {
		$arDepsList = $this->getDepsList();
		foreach($arDepsList as $depModID => $depModClass) {
			$depModInstallerFile = $this->bxModulesDir."/".$depModID."/install/index.php";
			if( is_file($depModInstallerFile) ) {
				require_once $depModInstallerFile;
				/** @var CModule $DepModInstaller */
				$bSuccess = true;
				$DepModInstaller = new $depModClass;
				$bSuccess = true;
				$bSuccess = $DepModInstaller->UnInstallTasks() && $bSuccess;
				$bSuccess = $DepModInstaller->UnInstallEvents() && $bSuccess;
				$bSuccess = $DepModInstaller->UnInstallFiles() && $bSuccess;
				$bSuccess = $DepModInstaller->UnInstallDB() && $bSuccess;
				if( $bSuccess ) {
					if( IsModuleInstalled($depModID) ) {
						UnRegisterModule($depModID);
					}
				}
			}
		}
		return true;
	}
	protected function getDepsList() {
		$arDepsList = array();
		if( is_dir($this->installDir."/modules") ) {
			if( ($dirSubModules = @opendir($this->installDir."/modules")) ) {
				while( ($depModID = readdir($dirSubModules)) !== false ) {
					if( $depModID == "." || $depModID == ".." ) {
						continue;
					}
					$arDepsList[$depModID] = str_replace('.', '_', $depModID);
				}
			}
		}
		return $arDepsList;
	}

	public function InstallData() {
		require_once $_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/" . $this->MODULE_ID . "/include.php";
		Currency::add(array(
			'CURRENCY' => 'RUB',
			'SORT' => '10'
		));
		Currency::add(array(
			'CURRENCY' => 'USD',
			'SORT' => '50'
		));
		Currency::setDefault('RUB');
		CurrencyFormat::add(array(
			'CURRENCY' => 'RUB',
			'NAME' => GetMessage('OBX_MARKET_INS_CURRRENCY_RUB'),
			'LANGUAGE_ID' => 'ru',
			'FORMAT' => GetMessage('OBX_MARKET_INS_CURRRENCY_RUB_FORMAT'),
			'THOUSANDS_SEP' => ' ',
		));
		CurrencyFormat::add(array(
			'CURRENCY' => 'RUB',
			'NAME' => 'Roubles',
			'LANGUAGE_ID' => 'en',
			'FORMAT' => '# Rub.',
			'THOUSANDS_SEP' => '\'',
		));
		CurrencyFormat::add(array(
			'CURRENCY' => 'USD',
			'NAME' => GetMessage('OBX_MARKET_INS_CURRRENCY_USD'),
			'LANGUAGE_ID' => 'ru',
			'FORMAT' => GetMessage('OBX_MARKET_INS_CURRRENCY_USD_FORMAT'),
			'THOUSANDS_SEP' => ' ',
		));
		CurrencyFormat::add(array(
			'CURRENCY' => 'USD',
			'NAME' => 'US Dollars',
			'LANGUAGE_ID' => 'en',
			'FORMAT' => '$#',
			'THOUSANDS_SEP' => '\'',
		));

		$priceID = Price::add(array(
			'CODE' => 'PRICE',
			'NAME' => GetMessage('OBX_MARKET_INS_BASE_PRICE'),
			'CURRENCY' => 'RUB',
			'SORT' => 10
		));
		Price::add(array(
			'CODE' => 'WHOLESALE',
			'NAME' => GetMessage('OBX_MARKET_INS_WHOLESALE_PRICE'),
			'CURRENCY' => 'RUB',
			'SORT' => 20
		));
		Price::setGroupList($priceID, array(2));
		OrderStatus::add(array(
			'CODE' => 'ACCEPTED',
			'NAME' => GetMessage('OBX_MARKET_INS_ORDER_STATUS_ACCEPTED'),
			'SORT' => '10',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => 'Y'
		));
		OrderStatus::add(array(
			'CODE' => 'COMPLETE',
			'NAME' => GetMessage('OBX_MARKET_INS_ORDER_STATUS_COMPLETE'),
			'COLOR' => '97c004',
			'SORT' => '1000',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => 'Y'
		));
		OrderStatus::add(array(
			'CODE' => 'CANCELED',
			'NAME' => GetMessage('OBX_MARKET_INS_ORDER_STATUS_CANCELED'),
			'COLOR' => 'D0D0D0',
			'SORT' => '10000',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => 'Y'
		));
		OrderProperty::add(array(
			'CODE' => 'IS_PAID',
			'NAME' => GetMessage('OBX_MARKET_INS_ORDER_PROP_IS_PAID'),
			'DESCRIPTION' => GetMessage('OBX_MARKET_INS_ORDER_PROP_IS_PAID_DESCR'),
			'SORT' => 100,
			'PROPERTY_TYPE' => 'C',
			'ACTIVE' => 'Y',
			'IS_SYS' => 'Y',
			'ACCESS' => 'R',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => 'Y'
		));
		$deliveryPropID = OrderProperty::add(array(
			'CODE' => 'DELIVERY',
			'NAME' => GetMessage('OBX_MARKET_INS_ORDER_PROP_DELIVERY'),
			'DESCRIPTION' => GetMessage('OBX_MARKET_INS_ORDER_PROP_DELIVERY_DESCR'),
			'SORT' => 100,
			'PROPERTY_TYPE' => 'L',
			'ACTIVE' => 'Y',
			'IS_SYS' => 'Y',
			'ACCESS' => 'W',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => 'Y'
		));
		if($deliveryPropID>0) {
			OrderPropertyEnum::add(array(
				'CODE' => '1',
				'PROPERTY_ID' => $deliveryPropID,
				'VALUE' => GetMessage('OBX_MARKET_INS_ORDER_PROP_DELIVERY_ENUM_1'),
				'SORT' => '10'
			));
			OrderPropertyEnum::add(array(
				'CODE' => '2',
				'PROPERTY_ID' => $deliveryPropID,
				'VALUE' => GetMessage('OBX_MARKET_INS_ORDER_PROP_DELIVERY_ENUM_2'),
				'SORT' => '20'
			));
		}
		$payMethodPropID = OrderProperty::add(array(
			'CODE' => 'PAYMENT',
			'NAME' => GetMessage('OBX_MARKET_INS_ORDER_PROP_PAYMENT'),
			'DESCRIPTION' => GetMessage('OBX_MARKET_INS_ORDER_PROP_PAYMENT_DESCR'),
			'SORT' => 100,
			'PROPERTY_TYPE' => 'L',
			'ACTIVE' => 'Y',
			'IS_SYS' => 'Y',
			'ACCESS' => 'W',
			'IS_SYS' => 'Y',
			OBX_MAGIC_WORD => 'Y'
		));
		if($payMethodPropID>0) {
			OrderPropertyEnum::add(array(
				'CODE' => '1',
				'PROPERTY_ID' => $payMethodPropID,
				'VALUE' => GetMessage('OBX_MARKET_INS_ORDER_PROP_PAYMENT_ENUM_1'),
				'SORT' => '10'
			));
		}
		return true;
	}
	public function UnInstallData() { return true; }


	protected function prepareDBConnection() {
		global $APPLICATION, $DB, $DBType;
		if (defined('MYSQL_TABLE_TYPE') && strlen(MYSQL_TABLE_TYPE) > 0) {
			$DB->Query("SET table_type = '" . MYSQL_TABLE_TYPE . "'", true);
		}
		if (defined('BX_UTF') && BX_UTF === true) {
			$DB->Query('SET NAMES "utf8"');
			//$DB->Query('SET sql_mode=""');
			$DB->Query('SET character_set_results=utf8');
			$DB->Query('SET collation_connection = "utf8_unicode_ci"');
		}
	}

	public function GetModuleRightList() {

	}

	static public function getModuleCurDir() {
		static $modCurDir = null;
		if ($modCurDir === null) {
			$modCurDir = str_replace("\\", "/", __FILE__);
			// 18 = strlen of "/install/index.php"
			$modCurDir = substr($modCurDir, 0, strlen($modCurDir) - 18);
		}
		return $modCurDir;
	}
	static public function includeLangFile() {
		global $MESS;
		@include(GetLangFileName(self::getModuleCurDir() . "/lang/", "/install/index.php"));
	}
}
