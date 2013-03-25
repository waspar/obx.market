<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ***************************************/

class OBX_Test_Util_ReinstallDB extends OBX_Market_TestCase {
	public function testReinstallDB() {
		require_once $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.market/install/index.php';
		$module_obx_market = new obx_market;
		$this->assertInstanceOf(obx_market, $module_obx_market);
		$module_obx_market->UnInstallDB();
		$module_obx_market->InstallDB();
		$module_obx_market->InstallData();
	}
}