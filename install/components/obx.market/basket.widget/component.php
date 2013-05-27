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

use \OBX\Core\Tools;
use \OBX\Core\JSLang;
use \OBX\Market\Basket;
use \OBX\Market\CIBlockPropertyPriceDBS;
use \OBX\Market\CurrencyFormat;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if( !CModule::IncludeModule('obx.market') ) {
	ShowError(GetMessage('OBX_MARKET_NOT_INSTALLED'));
	return false;
}
$arResult = array();
$Basket = Basket::getCurrent();

if( is_array($_REQUEST['add2Basket']) && count($_REQUEST['add2Basket'])>0 ) {
	foreach($_REQUEST['add2Basket'] as $productID => $quantity) {
		$productID = intval($productID);
		$quantity = intval($quantity);

		if( $Basket->isEmpty($productID) ) {
			$bSuccess = $Basket->addProduct($productID, $quantity);
		}
		else {
			$bSuccess = $Basket->setProductQuantity($productID, $quantity);
		}
		if(!$bSuccess) {
			$arResult['MESSAGES'][] = $Basket->popLastError('ARRAY');
		}
	}
}
if( isset($_REQUEST['updateBasket'])
	&& isset($_REQUEST['updateBasket']['id'])
	&& isset($_REQUEST['updateBasket']['qty'])
) {
	$productID = intval($_REQUEST['updateBasket']['id']);
	$quantity = intval($_REQUEST['updateBasket']['qty']);
	if($productID>0) {
		if( $Basket->isEmpty($productID) ) {
			$bSuccess = $Basket->addProduct($productID, $quantity);
		}
		else {
			$bSuccess = $Basket->setProductQuantity($productID, $quantity);
		}
		if(!$bSuccess) {
			$arResult['MESSAGES'][] = $Basket->popLastError('ARRAY');
		}
	}
}
if( isset($_REQUEST['removeFromBasket']) ) {
	$bSuccess = $Basket->removeProduct(intval($_REQUEST['removeFromBasket']));
	if(!$bSuccess) {
		$arResult['MESSAGES'][] = $Basket->popLastError('ARRAY');
	}
}

$arResult['ID'] = $Basket->getFields('ID');
$arResult['USER_ID'] = $Basket->getFields('USER_ID');
$arResult['HASH_STRING'] = $Basket->getFields('HASH_STRING');

$arCurrencyFormat = CurrencyFormat::getListArray(null, array(
	'CURRENCY' => $Basket->getFields('CURRENCY'),
	'LANGUAGE_ID' => LANGUAGE_ID
));
$arCurrencyFormat = $arCurrencyFormat[0];
$arResult['CURRENCY'] = array(
	'CURRENCY' => $arCurrencyFormat['CURRENCY'],
	'NAME' => $arCurrencyFormat['NAME'],
	'COURSE' => $arCurrencyFormat['CURRENCY_COURSE'],
	'RATE' => $arCurrencyFormat['CURRENCY_RATE'],
	'SORT' => $arCurrencyFormat['CURRENCY_SORT'],
	'IS_DEFAULT' => $arCurrencyFormat['CURRENCY_IS_DEFAULT'],
	'FORMAT' => array(
		'ID' => $arCurrencyFormat['ID'],
		'NAME' => $arCurrencyFormat['NAME'],
		'STRING' => $arCurrencyFormat['FORMAT'],
		'LANGUAGE_ID' => $arCurrencyFormat['LANGUAGE_ID'],
		'LANGUAGE_NAME' => $arCurrencyFormat['LANGUAGE_NAME'],
		'LANGUAGE_SORT' => $arCurrencyFormat['LANGUAGE_SORT'],
		'DEC_POINT' => $arCurrencyFormat['DEC_POINT'],
		'DEC_PRECISION' => $arCurrencyFormat['DEC_PRECISION'],
		'THOUSANDS_SEP' => $arCurrencyFormat['THOUSANDS_SEP'],
	)
);
$JSLang = JSLang::getInstance('obx.market');
$JSLang->addMessage('basket.currency.name', $arResult['CURRENCY']['NAME']);
$JSLang->addMessage('basket.currency.format.string', $arResult['CURRENCY']['FORMAT']['STRING']);
$JSLang->addMessage('basket.currency.format.dec_point', $arResult['CURRENCY']['FORMAT']['DEC_POINT']);
$JSLang->addMessage('basket.currency.format.dec_precision', $arResult['CURRENCY']['FORMAT']['DEC_PRECISION']);
$JSLang->addMessage('basket.currency.format.thousands_sep', $arResult['CURRENCY']['FORMAT']['THOUSANDS_SEP']);
Tools::addDeferredJS("/bitrix/js/obx.market/jscrollpane.min.js");
Tools::addDeferredJS("/bitrix/js/obx.market/obx.basket.js");


$arResult['BASKET_COST'] = $Basket->getCost();
$arResult['PRODUCTS_COUNT'] = $Basket->getProductsCount();
$arResult['PRODUCTS_LIST'] = $Basket->getProductsList();

if( $arResult['PRODUCTS_LIST']>0 ) {
	foreach($arResult['PRODUCTS_LIST'] as &$arItem) {
		$arItem['IB_ELEMENT']['PREVIEW_PICTURE'] = CFile::GetFileArray($arItem['IB_ELEMENT']['PREVIEW_PICTURE']);
		$arItem['IB_ELEMENT']['DETAIL_PICTURE'] = CFile::GetFileArray($arItem['IB_ELEMENT']['DETAIL_PICTURE']);
	}
}

$this->IncludeComponentTemplate();

return $arResult['ITEMS_COUNT'];
