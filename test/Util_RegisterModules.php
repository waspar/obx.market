<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/
class OBX_Test_Util_RegisterModules extends OBX_Market_TestCase {
	public function testRegisterModules(){
		if( ! IsModuleInstalled('obx.core') ) {
			RegisterModule('obx.core');
		}
		if( ! IsModuleInstalled('obx.market') ) {
			RegisterModule('obx.market');
		}
		$this->assertTrue(IsModuleInstalled('obx.core'));
		$this->assertTrue(IsModuleInstalled('obx.market'));

		require_once $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.core/install/index.php';
		require_once $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.market/install/index.php';
		$obx_core = new obx_core();
		$obx_core->InstallEvents();
		$obx_market = new obx_market();
		$obx_market->InstallEvents();
	}
}