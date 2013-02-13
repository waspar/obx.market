<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( $USER->IsAdmin() ) {
	require $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/obx.market/install/index.php";
	$module_obx_market = new obx_market;
	$module_obx_market->InstallDB();
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>