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

use \OBX\Market\Basket;
use \OBX\Market\CIBlockPropertyPriceDBS;
use \OBX\Core\Tools;

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

$arResult['BASKET_COST'] = $Basket->getCost();
$arResult['PRODUCTS_COUNT'] = $Basket->getProductsCount();
$arResult['PRODUCTS_LIST'] = $Basket->getProductsList();

if( $arResult['PRODUCTS_LIST']>0 ) {
	foreach($arResult['PRODUCTS_LIST'] as &$arItem) {
		$arItem['IB_ELEMENT']['PREVIEW_PICTURE'] = CFile::GetFileArray($arItem['IB_ELEMENT']['PREVIEW_PICTURE']);
		$arItem['IB_ELEMENT']['DETAIL_PICTURE'] = CFile::GetFileArray($arItem['IB_ELEMENT']['DETAIL_PICTURE']);
	}
	$arResult['PRODUCTS_LIST'][$arIBProduct['PRODUCT_ID']] = $arIBProduct;
}

$this->IncludeComponentTemplate();

return $arResult['ITEMS_COUNT'];
