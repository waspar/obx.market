<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ) {
	$db_events = GetModuleEvents("iblock", "OnBeforeIBlockPropertyDelete");
	while($arEvent = $db_events->Fetch()) {
		wd($arEvent, '$arEvent');
	}

}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>