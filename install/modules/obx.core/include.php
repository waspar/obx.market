<?php
/*****************************************
 ** @product Market-Start Bitrix Module **
 ** @vendor A68 Studio                  **
 ** @mailto info@a-68.ru                **
 *****************************************/

if(!CModule::IncludeModule('iblock')){
	return false;
}

$arModuleClasses = require dirname(__FILE__).'/classes/.classes.php';
CModule::AddAutoloadClasses('obx.core', $arModuleClasses);
?>