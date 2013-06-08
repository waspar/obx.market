<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if( ! CModule::IncludeModule('obx.market') ) {
	ShowError(GetMessage('OBX_MARKET_MODULES_NOT_INSTALLED'));
	return;
}

///////// PARAMS /////////
$arParams['SHOW_ITEMS'] = ($arParams['SHOW_ITEMS']=='Y')?true:false;


$arResult['USER'] = null;
wd($arParams, '$arParams');
if( $USER->IsAuthorized() ) {
	//$rsCurrentUser = CUser::GetByID(3);
	$rsCurrentUser = CUser::GetByID($USER->GetID());
	if( $arCurrentUser = $rsCurrentUser->Fetch() ) {
		$arResult['USER'] = $arCurrentUser;
	}
	$arOrdersFilter = array();
	if( array_key_exists('STATUS', $arParams['SHOW_FIELDS']) ) {
		$arOrdersSelect[] = 'STATUS_ID';
		$arOrdersSelect[] = 'STATUS_CODE';
		$arOrdersSelect[] = 'STATUS_NAME';
	}
	if( array_key_exists('DATE_CREATED', $arParams['SHOW_FIELDS']) ) {
		$arOrdersSelect[] = 'DATE_CREATED';
	}
	if( array_key_exists('TIMESTAMP_X', $arParams['SHOW_FIELDS']) ) {
		$arOrdersSelect[] = 'TIMESTAMP_X';
	}
	if( array_key_exists('ITEMS_COST', $arParams['SHOW_FIELDS']) ) {
		$arOrdersSelect[] = 'ITEMS_COST';
	}

	/////////// USER ORDERS LIST ///////////
	$arOrderListSort = array(
		'DATE_CREATED'
	);
	$arOrderListFilter = array(
		'USER_ID' => $arResult['USER']['ID']
	);
	$arOrderListSelect = array(
		'ID',
		'DATE_CREATED',
		'TIMESTAMP_X',
		'USER_ID',
		'USER_NAME',
		'STATUS_ID',
		'STATUS_CODE',
		'STATUS_NAME',
		'STATUS_DESCRIPTION',
		'CURRENCY',
		'ITEMS_COST',
	);
	$arResult['ORDERS_LIST'] = OBX_OrderList::getListArray(
		$arOrderListSort, $arOrderListFilter, null, null, $arOrderListSelect
	);
	$arOrderIDList = array();
	foreach($arResult['ORDERS_LIST'] as &$arOrder) {
		$arOrderIDList[] = $arOrder['ID'];
	}

	/////////// ORDER PROPERTIES ///////////
	$arPropSort = array(
		'ORDER_ID' => 'ASC',
		'ID' => 'ASC'
	);
	$arPropFilter = array(
		'ORDER_USER_ID' => $arResult['USER']['ID'],
		'ORDER_ID' => $arOrderIDList
	);
	$arPropSelect = array(
		'ID',
		'ORDER_ID',
		'ORDER_USER_ID',
		'PROPERTY_ID',
		'PROPERTY_CODE',
		'PROPERTY_NAME',
		'PROPERTY_TYPE',
		'PROPERTY_SORT',
		'PROPERTY_IS_SYS',
		'VALUE',
		'VALUE_ENUM_ID',
	);
	$rsUserOrderProps = OBX_OrderPropertyValues::getList($arPropSort, $arPropFilter, null, null, $arPropSelect);
	$arUserOrderProps = array();
	while( $arOrderPropValue = $rsUserOrderProps->Fetch() ) {
		if( !array_key_exists($arOrderPropValue['ORDER_ID'], $arUserOrderProps) ) {
			$arUserOrderProps[$arOrderPropValue['ORDER_ID']] = array();
		}
		$arUserOrderProps[$arOrderPropValue['ORDER_ID']][$arOrderPropValue['PROPERTY_CODE']] = $arOrderPropValue;
	}

	/////////// ORDER ITEMS ///////////
	if( $arParams['SHOW_ITEMS'] ) {
		$arItemsSort = array(
			'ORDER_ID' => 'ASC',
			'ID' => 'ASC'
		);
		$arItemsFilter = array(
			'ORDER_USER_ID' => $arResult['USER']['ID'],
			'ORDER_ID' => $arOrderIDList
		);
		$arItemsSelect = array(
			'ID',
			'ORDER_ID',
			'ORDER_USER_ID',
			'PRODUCT_ID',
			'PRODUCT_NAME',
			'QUANTITY',
			'WEIGHT',
			'PRICE_ID',
			'PRICE_CODE',
			'PRICE_NAME',
			'PRICE_VALUE',
			'DISCOUNT_VALUE',
			'VAT_ID',
			'VAT_VALUE',
			'IB_ELT_ID',
			'IB_ELT_NAME',
			'IB_ELT_CODE',
			'IB_ELT_SECTION_ID',
			'IB_ELT_SECTION_CODE',
			'IB_ELT_SORT',
			'IB_ELT_PREVIEW_TEXT',
			'IB_ELT_PREVIEW_PICTURE',
			'IB_ELT_DETAIL_TEXT',
			'IB_ELT_DETAIL_PICTURE',
			'IB_ELT_XML_ID',
			'IB_ELT_TIMESTAMP_X',
			'IB_ELT_MODIFIED_BY',
			'IB_ELT_LIST_PAGE_URL',
			'IB_ELT_SECTION_PAGE_URL',
			'IB_ELT_DETAIL_PAGE_URL',
			'IB_ELT_SITE_ID',
			'IB_ELT_SITE_DIR'
		);
		$arUserOrderItems = array();
		$rsUserOrderItems = OBX_OrderItems::getList($arItemsSort, $arItemsFilter, null, null, $arItemsSelect);
		while( $arItem = $rsUserOrderItems->Fetch() ) {
			if( !array_key_exists($arItem['ORDER_ID'], $arUserOrderItems) ) {
				$arUserOrderItems[$arItem['ORDER_ID']] = array();
			}
			$arItem['IBLOCK_ELEMENT'] = array();
			foreach($arItem as $fldName => &$fldValue) {
				if( strpos($fldName, 'IB_ELT') !== false ) {
					if($fldValue) {
						$arItem['IBLOCK_ELEMENT'][substr($fldName, 7)] = $fldValue;
					}
					unset($arItem[$fldName]);
				}
			}
			if($arItem['IBLOCK_ELEMENT']['ID']) {
				$arUrlReplaceTarget = array(
					'#SITE_DIR#',
					'#SECTION_CODE#',
					'#SECTION_ID#',
					'#CODE#',
					'#ID#',
					'//'
				);
				$arUrlReplaceValue = array(
					$arItem['IBLOCK_ELEMENT']['SITE_DIR'],
					$arItem['IBLOCK_ELEMENT']['SECTION_CODE'],
					$arItem['IBLOCK_ELEMENT']['SECTION_ID'],
					$arItem['IBLOCK_ELEMENT']['CODE'],
					$arItem['IBLOCK_ELEMENT']['ID'],
					'/'
				);
				$arItem['IBLOCK_ELEMENT']['LIST_PAGE_URL'] = str_replace($arUrlReplaceTarget, $arUrlReplaceValue, $arItem['IBLOCK_ELEMENT']['LIST_PAGE_URL']);
				$arItem['IBLOCK_ELEMENT']['SECTION_PAGE_URL'] = str_replace($arUrlReplaceTarget, $arUrlReplaceValue, $arItem['IBLOCK_ELEMENT']['LIST_PAGE_URL']);
				$arItem['IBLOCK_ELEMENT']['DETAIL_PAGE_URL'] = str_replace($arUrlReplaceTarget, $arUrlReplaceValue, $arItem['IBLOCK_ELEMENT']['LIST_PAGE_URL']);
			}

			$arUserOrderItems[$arItem['ORDER_ID']][$arItem['ID']] = $arItem;
		}
	}

	foreach($arResult['ORDERS_LIST'] as &$arOrder) {
		if( array_key_exists($arOrder['ID'], $arUserOrderProps) ) {
			$arOrder['PROPERTIES'] = $arUserOrderProps[$arOrder['ID']];
		}
		if( array_key_exists($arOrder['ID'], $arUserOrderItems) ) {
			$arOrder['ITEMS_LIST'] = $arUserOrderItems[$arOrder['ID']];
		}
	}
	unset($arUserOrderItems);

	wd($arResult['USER'], "\$arResult['USER']");
	wd($arResult['ORDERS_LIST'], "\$arResult['ORDERS_LIST']");
}
else {

}

$this->IncludeComponentTemplate();
?>