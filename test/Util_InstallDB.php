<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ***************************************/

class OBX_Test_Util_InstallDB extends OBX_Market_TestCase {
	public function testInstallDB() {
		$module_obx_market = new obx_market;
		$this->assertInstanceOf(obx_market, $module_obx_market);
		$module_obx_market->InstallDB();
		$module_obx_market->InstallData();
	}
}