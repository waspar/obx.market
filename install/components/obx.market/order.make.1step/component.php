<?
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/
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
	$arBasketItem = $Basket->getItemsList();


}

$arResult = array();

$this->IncludeComponentTemplate();

?>