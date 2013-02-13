<?
/*****************************************
 ** @vendor A68 Studio                  **
 ** @mailto info@a-68.ru                **
 ** @time 12:13                         **
 ** @user tashiro                       **
 *****************************************/

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?
	wd(OBX_Price::getAvailPriceForUser(1),"avail");
?>
<?endif;?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>