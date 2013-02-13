<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
IncludeModuleLangFile(__FILE__);

$arJSON = array(
	'messages' => array()
);
for($oneCycle = 0; $oneCycle < 1; $oneCycle++)
{
	if( !CModule::IncludeModule('obx.market') ) {
		$arJSON['messages'][] = array(
			'TYPE' => 'E',
			'TEXT' => GetMessage('OBX_MARKET_MODULE_NOT_INSTALLED'),
			'CODE' => 1
		);
		break;
	}

	$Basket = OBX_Basket::getInstance();

	if( is_array($_REQUEST['add']) && count($_REQUEST['add'])>0 ) {
		foreach($_REQUEST['add'] as $productID => $quantity) {
			$productID = intval($productID);
			$quantity = intval($quantity);

			if( $Basket->isEmpty($productID) ) {
				$bSuccess = $Basket->addItem($productID, $quantity);
			}
			else {
				$bSuccess = $Basket->setItemCount($productID, $quantity);
			}
			if(!$bSuccess) {
				$arJSON['messages'][] = $Basket->popLastError('ARRAY');
			}
		}
	}
	if( isset($_REQUEST['update'])
		&& isset($_REQUEST['update']['id'])
		&& isset($_REQUEST['update']['qty'])
	) {
		$productID = intval($_REQUEST['update']['id']);
		$quantity = intval($_REQUEST['update']['qty']);
		if($productID>0) {
			if( $Basket->isEmpty($productID) ) {
				$bSuccess = $Basket->addItem($productID, $quantity);
			}
			else {
				$bSuccess = $Basket->setItemCount($productID, $quantity);
			}
			if(!$bSuccess) {
				$arJSON['messages'][] = $Basket->popLastError('ARRAY');
			}
		}
	}
	if( isset($_REQUEST['remove']) ) {
		$bSuccess = $Basket->removeProduct(intval($_REQUEST['remove']));
		if(!$bSuccess) {
			$arJSON['messages'][] = $Basket->popLastError('ARRAY');
		}
	}

	$arJSON['basket_count'] = $Basket->getBasketCost();
	$arJSON['items_count'] = $Basket->getItemsCount();
	$arJSON['items_list'] = $Basket->getItemsList();
	$arJSON['products_count'] = $Basket->getProductsCount();
	$arProductList = $Basket->getProductsList();
	foreach($arProductList as &$arProduct) {
		$arJsonProduct = array(
			'id' => $arProduct['ID'],
			'href' => $arProduct['DETAIL_PAGE_URL'],
			'name' => $arProduct['NAME'],
			'value' => '1',
			'price_type' => $arProduct['OPTIMAL_PRICE'],
			'price' => $arProduct['PRICE_LIST'][$arProduct['OPTIMAL_PRICE']]['TOTAL_VALUE'],
			'section_id' => $arProduct['IBLOCK_SECTION_ID']
		);
		foreach($arProduct['PROPERTIES'] as &$arProperty) {
			if($arProperty["PROPERTY_TYPE"]=="L") {
				$arJsonProduct["prop_".$arProperty["ID"]] = $arProperty["VALUE_ENUM_ID"];
			}
			else {
				$arJsonProduct["prop_".$arProperty["ID"]] = $arProperty["VALUE"];
			}
		}
		$arJSON['products_list'][] = $arJsonProduct;
	}


}
echo json_encode($arJSON);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>