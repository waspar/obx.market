<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetPageProperty("__hide_footer", "Y");?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?
	OBX_ECommerceIBlock::unRegisterModuleDependencies();
	OBX_ECommerceIBlock::registerModuleDependencies();
	OBX_CIBlockPropertyPrice::unRegisterModuleDependencies();
	OBX_CIBlockPropertyPrice::registerModuleDependencies();
?>
<?endif?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>