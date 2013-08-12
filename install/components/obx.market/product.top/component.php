<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\Price;
use OBX\Market\Basket;
use OBX\Core\Tools;

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CDatabase $DB
 * @var CUser $USER
 */

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('obx.market')) {
	return;
}

/*************************************************************************
 *        Processing of the Buy link
 *************************************************************************/
$strError = '';
if (array_key_exists($arParams['ACTION_VARIABLE'], $_REQUEST) && array_key_exists($arParams['PRODUCT_ID_VARIABLE'], $_REQUEST)) {
	$Basket = Basket::getCurrent();
	$q = 1;
	if ($arParams['USE_QUANTITY_VARIABLE'] == 'Y' && array_key_exists($arParams['QUANTITY_VARIABLE'], $_REQUEST)) {
		$rQ = intval($_REQUEST[$arParams['QUANTITY_VARIABLE']]);
		$q = ($rQ > 0) ? $rQ : 1;
	}

	switch ($_REQUEST[$arParams['ACTION_VARIABLE']]) {
		case 'ADD' :
			$Basket->addProduct($_REQUEST[$arParams['PRODUCT_ID_VARIABLE']], $q);
			break;
		case 'BUY' :
			$Basket->addProduct($_REQUEST[$arParams['PRODUCT_ID_VARIABLE']], $q);
			LocalRedirect($arParams['PATH_TO_BASKET']);
			break;
		case 'DEL' :
			$Basket->removeProduct($_REQUEST[$arParams['PRODUCT_ID_VARIABLE']]);
			break;
		default :
			break;
	}
	LocalRedirect($APPLICATION->GetCurPageParam('', array($arParams['PRODUCT_ID_VARIABLE'], $arParams['ACTION_VARIABLE'], $arParams['QUANTITY_VARIABLE'])));
}

if ($this->StartResultCache()) {
	$arItems = array();

	$arProductSelect = array(
		 'NAME'
		,'ID'
		,'DATE_CREATE'
		,'DATE_CREATE_UNIX'
		,'IBLOCK_ID'
		,'IBLOCK_SECTION_ID'
		,'PREVIEW_PICTURE'
		,'PREVIEW_TEXT'
		,'PREVIEW_TEXT_TYPE'
		,'DETAIL_PICTURE'
		,'DETAIL_TEXT'
		,'DETAIL_TEXT_TYPE'
		,'SEARCHABLE_CONTENT'
		,'CODE'
		,'TAGS'
		,'IBLOCK_TYPE_ID'
		,'IBLOCK_CODE'
		,'IBLOCK_NAME'
		,'DETAIL_PAGE_URL'
		,'LIST_PAGE_URL'
	);
	$arSort = array('SORT' => 'ASC');

	$arNavStartParams = array();

	$arIBlockFilter = array('ID' => array());
	$arProductFilter = array('LOGIC' => 'OR');

	foreach($arParams['IBLOCK_ID_LIST'] as &$iblockID) {
		$iblockID = intval($iblockID);
		$arSectionFilter['IBLOCK_ID'][] = $iblockID;
		$arIBlockFilter['ID'][] = $iblockID;
	}

	if( empty($arIBlockFilter['ID']) ) {
		ShowError(GetMessage('OBX_MARKET_CMP_PROD_TOP_FILTER_IB_NOT_SET'));
		return false;
	}

	$dbIBlockList = CIBlock::GetList(array(), $arIBlockFilter);
	$arIBlockList = array();
	while( $arIBlock = $dbIBlockList->Fetch() ) {
		$arIBlock['FILTER_PROPERTY'] = array();
		if( array_key_exists('IBLOCK_PROP_'.$arIBlock['CODE'], $arParams) ) {
			$dbProperty = CIBlockProperty::GetList(
				array(),
				array(
					'IBLOCK_ID' => $arIBlock['ID'],
					'CODE' => $arParams['IBLOCK_PROP_'.$arIBlock['CODE']]
				));
			if( $arProperty = $dbProperty->Fetch() ) {
				$arIBlock['FILTER_PROPERTY'] = array(
					'ID' => $arProperty['ID'],
					'NAME' => $arProperty['NAME'],
					'CODE' => $arProperty['CODE'],
					'PROPERTY_TYPE' => $arProperty['PROPERTY_TYPE'],
				);
			}
		}
		$arIBlockList[] = array(
			'ID' => $arIBlock['ID'],
			'NAME' => $arIBlock['NAME'],
			'CODE' => $arIBlock['CODE'],
			'FILTER_PROPERTY' => $arIBlock['FILTER_PROPERTY'],
		);
	}
	$arIBlockListIDIndex = Tools::getListIndex($arIBlockList, 'ID', true, true);
	//$arIBlockListCodeIndex = Tools::getListIndex($arIBlockList, 'CODE', true, true);

	$bFilterIsEmpty = true;
	foreach($arIBlockListIDIndex as $iblockID => $arIBlock) {
		if( !empty($arIBlock['FILTER_PROPERTY']) ) {
			$bFilterIsEmpty = false;
			$arProductFilter[] = array(
				'ACTIVE' => 'Y',
				'IBLOCK_ID' => $iblockID,
				'!PROPERTY_'.$arIBlock['FILTER_PROPERTY']['CODE'] => false
			);
		}
	}

	if( $bFilterIsEmpty ) {
		ShowError(GetMessage('OBX_MARKET_CMP_PROD_TOP_FILTER_IS_NOT_SET'));
		return false;
	}

	$dbItems = CIBlockElement::GetList($arSort, $arProductFilter,
		false, //mixed arGroupBy
		false, //mixed arNavStartParams
		$arProductSelect
	);

	$bPriceFound = true;
	$iItem = 0;
	while ($obElement = $dbItems->GetNextElement()) {

		$arItem = $obElement->GetFields();
		$arItem['PROPERTIES'] = $obElement->GetProperties();

		$arButtons = CIBlock::GetPanelButtons(
			$arItem['IBLOCK_ID'],
			$arItem['ID'],
			0,
			array('SECTION_BUTTONS' => false, 'SESSID' => false)
		);

		$arItem['EDIT_LINK'] = $arButtons['edit']['edit_element']['ACTION_URL'];
		$arItem['DELETE_LINK'] = $arButtons['edit']['delete_element']['ACTION_URL'];

		$arItem['PREVIEW_PICTURE'] = CFile::GetFileArray($arItem['PREVIEW_PICTURE']);
		$arItem['DETAIL_PICTURE'] = CFile::GetFileArray($arItem['DETAIL_PICTURE']);

		/*
		 * Mapping arPrices from Bitrix:CIBlockPriceTools::GetItemPrices();
		 */
		/*
		$arItem['PRICES'] = array(
			'VALUE_NOVAT', // цена без налога
			'PRINT_VALUE_NOVAT', // цена без налога для вывода

			'VALUE_VAT', // цена с налогом
			'PRINT_VALUE_VAT', // цена с налогом для вывода

			'VATRATE_VALUE', // процент налога
			'PRINT_VATRATE_VALUE', // процент налога для вывода

			'DISCOUNT_VALUE_NOVAT', // сумма скидки без налога
			'PRINT_DISCOUNT_VALUE_NOVAT', // сумма скидки без налога для вывода

			'DISCOUNT_VALUE_VAT', // сумма скидки с налогом
			'PRINT_DISCOUNT_VALUE_VAT', // сумма скидки с налогом для вывода

			'DISCOUNT_VATRATE_VALUE', // процент налога для суммы скидки
			'PRINT_DISCOUNT_VATRATE_VALUE', // процент налога для суммы скидки для вывода

			'CURRENCY', // код валюты
			'ID', // ID ценового предложения
			'CAN_ACCESS', // возможность просмотра - Y/N
			'CAN_BUY', // возможность купить - Y/N
			'VALUE', // цена
			'PRINT_VALUE', // отформатированная цена для вывода
			'DISCOUNT_VALUE', // цена со скидкой
			'PRINT_DISCOUNT_VALUE', // отформатированная цена со скидкой
		);
		*/
		$arItem['PRICE'] = null; // Нужная цена
		$arSupportData = array();

		$arItemPrices = Price::getProductPriceList($arItem['ID']);
		foreach ($arItemPrices as &$arPrice) {
			if ($arPrice['IS_OPTIMAL'] == 'Y' && $arPrice['AVAILABLE'] == 'Y') { // IS_OPTIMAL может быть == Y только 1 раз
				$arItem['PRICE'] = $arPrice;
				$arItem['CAN_BUY'] = 'Y';
				$arSupportData['WEIGHT']['ID'] = $arPrice['WEIGHT_VAL_PROP_ID'];
				$arSupportData['DISCOUNT']['ID'] = $arPrice['DISCOUNT_VAL_PROP_ID'];
			}
			$arItem['PRICES'][$arPrice['PRICE_CODE']] = array(
				'VALUE_NOVAT' => $arPrice['TOTAL_VALUE'],
				'PRINT_VALUE_NOVAT' => $arPrice['TOTAL_VALUE_FORMATTED'],

				'VALUE_VAT' => $arPrice['TOTAL_VALUE'],
				'PRINT_VALUE_VAT' => $arPrice['TOTAL_VALUE_FORMATTED'],

				'VATRATE_VALUE' => 'NULL',
				'PRINT_VATRATE_VALUE' => '0%',

				'DISCOUNT_VALUE_NOVAT' => $arPrice['DISCOUNT_VALUE'],
				'PRINT_DISCOUNT_VALUE_NOVAT' => $arPrice['DISCOUNT_VALUE_FORMATTED'],

				'DISCOUNT_VALUE_VAT' => $arPrice['DISCOUNT_VALUE'],
				'PRINT_DISCOUNT_VALUE_VAT' => $arPrice['DISCOUNT_VALUE_FORMATTED'],

				'DISCOUNT_VATRATE_VALUE' => 'NULL',
				'PRINT_DISCOUNT_VATRATE_VALUE' => '0%',

				'CURRENCY' => $arPrice['PRICE_CURRENCY'],
				'ID' => $arPrice['PRICE_ID'],
				'CAN_ACCESS' => $arPrice['AVAILABLE'],
				'CAN_BUY' => $arPrice['AVAILABLE'],
				'VALUE' => $arPrice['VALUE'],
				'PRINT_VALUE' => $arPrice['VALUE_FORMATTED'],
				'DISCOUNT_VALUE' => $arPrice['DISCOUNT_VALUE'],
				'PRINT_DISCOUNT_VALUE' => $arPrice['DISCOUNT_VALUE_FORMATTED'],
			);
		}
		unset($arPrice);

		$arItem['BUY_URL'] = htmlspecialchars($APPLICATION->GetCurPageParam($arParams['ACTION_VARIABLE'] . '=BUY&' . $arParams['PRODUCT_ID_VARIABLE'] . '=' . $arItem['ID'], array($arParams['PRODUCT_ID_VARIABLE'], $arParams['ACTION_VARIABLE'])));
		$arItem['ADD_URL'] = htmlspecialchars($APPLICATION->GetCurPageParam($arParams['ACTION_VARIABLE'] . '=ADD&' . $arParams['PRODUCT_ID_VARIABLE'] . '=' . $arItem['ID'], array($arParams['PRODUCT_ID_VARIABLE'], $arParams['ACTION_VARIABLE'])));
		$arItem['DEL_URL'] = htmlspecialchars($APPLICATION->GetCurPageParam($arParams['ACTION_VARIABLE'] . '=DEL&' . $arParams['PRODUCT_ID_VARIABLE'] . '=' . $arItem['ID'], array($arParams['PRODUCT_ID_VARIABLE'], $arParams['ACTION_VARIABLE'])));


		if (empty($arItem['PRICE'])) {
			$bPriceFound = false;
		}

		$arItems[$iItem] = $arItem;

		if (!empty ($arSupportData['WEIGHT']['ID'])) {
			$resWeight = CIBlockProperty::GetByID(
				$arSupportData['WEIGHT']['ID'],
				$arParams['IBLOCK_ID']
			);
			$arWeight = $resWeight->GetNext();
			unset ($resWeight);
			if (!empty($arWeight['CODE'])) {
				$arSupportData['WEIGHT']['CODE'] = $arWeight['CODE'];
			}
			unset ($arWeight);
		}
		if (!empty ($arSupportData['DISCOUNT']['ID'])) {
			$resDiscount = CIBlockProperty::GetByID(
				$arSupportData['DISCOUNT']['ID'],
				$arParams['IBLOCK_ID']
			);
			$arDiscount = $resDiscount->GetNext();
			unset ($resDiscount);
			if (!empty($arDiscount['CODE'])) {
				$arSupportData['DISCOUNT']['CODE'] = $arDiscount['CODE'];
			}
			unset ($arDiscount);
		}
		$iItem++;
	}
	if (!$bPriceFound) {
		$this->AbortResultCache();
		$arResult['ERROR'] = GetMessage('OBX_MARKET_CMP_CAN_NOT_FIND_PRICE');
	} else {
		$arResult['ERROR'] = null;
	}
	$arResult['ITEMS'] = $arItems;
	$arResult['ITEMS_COUNT'] = $iItem;
	$arResult['SUPPORT_DATA'] = $arSupportData;
	unset ($arItems);
	unset ($arSupportData);

	$this->IncludeComponentTemplate();
}
