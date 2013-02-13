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
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if( !CModule::IncludeModule('obx.market') ) {
	ShowError(GetMessage('OBX_MARKET_NOT_INSTALLED'));
	return false;
}
$arResult = array();
$Basket = OBX_Basket::getInstance();

if( is_array($_REQUEST['add2Basket']) && count($_REQUEST['add2Basket'])>0 ) {
	foreach($_REQUEST['add2Basket'] as $productID => $quantity) {
		$productID = intval($productID);
		$quantity = intval($quantity);

		if( $Basket->isEmpty($productID) ) {
			$bSuccess = $Basket->addItem($productID, $quantity);
		}
		else {
			$bSuccess = $Basket->setItemCount($productID, $quantity);
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
				$bSuccess = $Basket->addItem($productID, $quantity);
			}
			else {
				$bSuccess = $Basket->setItemCount($productID, $quantity);
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

	$arResult['BASKET_COST'] = $Basket->getBasketCost();
	$arResult['ITEMS_COUNT'] = $Basket->getItemsCount();
	$arResult['ITEMS_LIST'] = $Basket->getItemsList();
	$arResult['PRODUCTS_COUNT'] = $Basket->getProductsCount();
	$arBasketProductList = $Basket->getProductsList();
	$arResult['PRODUCTS_LIST'] = array();

	if( $arResult['ITEMS_COUNT']>0 ) {
		$arIBProductListFilter = array("LOGIC" => "OR");
		foreach($arBasketProductList as $productID => &$arProduct) {
			$arIBProductListFilter[] = array(
				"IBLOCK_ID" => $arProduct["IBLOCK_ID"],
				"ID" => $arProduct["ID"]
			);
		}
		$rsIBProductList = CIBlockElement::GetList(array(), $arIBProductListFilter);
		while($obIBProduct = $rsIBProductList->GetNextElement()) {
			$arIBProduct = $obIBProduct->GetFields();
			$arIBProduct['PREVIEW_PICTURE'] = CFile::GetFileArray($arIBProduct['PREVIEW_PICTURE']);
			$arIBProduct['DETAIL_PICTURE'] = CFile::GetFileArray($arIBProduct['DETAIL_PICTURE']);
			$arIBProduct['PRICE_LIST'] = $arBasketProductList[$arIBProduct['ID']]['PRICE_LIST'];
			$arIBProduct['OPTIMAL_PRICE'] = $arBasketProductList[$arIBProduct['ID']]['OPTIMAL_PRICE'];
			$arIBProduct['PROPERTIES'] = $obIBProduct->GetProperties();
			$arResult['PRODUCTS_LIST'][$arIBProduct['ID']] = $arIBProduct;
		}
	}

$this->IncludeComponentTemplate();

return $arResult['ITEMS_COUNT'];
?>