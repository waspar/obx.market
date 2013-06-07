<?
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('iblock')) {
	ShowError(GetMessage('OBX_IBLOCK_NOT_INSTALLED'));
	return false;
}
if (!CModule::IncludeModule('obx.market')) {
	ShowError(GetMessage('OBX_MARKET_NOT_INSTALLED'));
	return false;
}

if( $_REQUEST['action'] == 'make_order' ) {
	$Basket = OBX_Basket::getInstance();
	$arBasketItems = $Basket->getItemsList();


}

$arResult = array();

$this->IncludeComponentTemplate();

?>