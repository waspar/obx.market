<?php
/***********************************************
 ** @product OBX:Core Bitrix Module           **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
if( !CModule::IncludeModule('iblock') ) return;

//$arTypesEx = CIBlockParameters::GetIBlockTypes(Array('__all__'=>GetMessage('OBXCMPP_MIL_ALL_IBLOCKS')));
$arTypesEx = CIBlockParameters::GetIBlockTypes();

$arIBlockFilter = array();
if( isset($_REQUEST['site']) ) {
	$arIBlockFilter['SITE_ID'] = $_REQUEST['site'];
}
if( is_array($arCurrentValues['IBLOCK_TYPE_LIST']) && !empty($arCurrentValues['IBLOCK_TYPE_LIST'])) {
	$arIBlockFilter['TYPE'] = array();
	foreach($arCurrentValues['IBLOCK_TYPE_LIST'] as $currentValueIBlockType) {
		$arIBlockFilter['TYPE'][] = $currentValueIBlockType;
		$arIBlockPropFilter['TYPE'][] = $currentValueIBlockType;
	}
}

$arIBlocks=array();
$arSelectedIBlockList = array();
$arIBlockPropFilter = array(
	'ACTIVE' => 'Y',
	'IBLOCK_ID' => array(),
);
$db_iblock = CIBlock::GetList(Array('SORT'=>'ASC'), $arIBlockFilter);
while( $arRes = $db_iblock->Fetch() ) {
	$arIBlocks[$arRes['ID']] = $arTypesEx[$arRes['IBLOCK_TYPE_ID']].': '.$arRes['NAME'];
	if( in_array($arRes['ID'], $arCurrentValues['IBLOCK_ID_LIST']) ) {
		$arSelectedIBlockList[$arRes['ID']] = $arRes;
		$arIBlockPropFilter['IBLOCK_ID'] = $arRes['ID'];
	}
}

$arProperties = array();
$rsProperties = CIBlockProperty::GetList(Array('sort'=>'asc', 'id'=>'asc'), $arIBlockPropFilter);
while( $arProp = $rsProperties->GetNext() ) {
	$arProperties[$arProp['IBLOCK_CODE']][$arProp['CODE']] = $arProp['NAME'];
}

$arComponentParameters = array(
	'GROUPS' => array(
		'IBLOCK_PROPERTIES' => array('NAME' => GetMessage('OBXMCMPP_PROD_PRMGRP_IBPROPS'), 'SORT' => 210),
		'SETTINGS' => array('NAME' => GetMessage('OBXMCMPP_PROD_PRMGRP_SETTINGS'), 'SORT' => 220),
	),
	'PARAMETERS' => array(
		'IBLOCK_TYPE_LIST' => Array(
			'PARENT' => 'DATA_SOURCE',
			'NAME'=>GetMessage('OBXMCMPP_PROD_IBLOCK_TYPE_LIST'),
			'TYPE'=>'LIST',
			'VALUES'=>$arTypesEx,
			'DEFAULT'=>'catalog',
			'ADDITIONAL_VALUES'=>'N',
			'REFRESH' => 'Y',
			'MULTIPLE'=>'Y',
		),
		'IBLOCK_ID_LIST' => Array(
			'PARENT' => 'DATA_SOURCE',
			'NAME'=>GetMessage('OBXMCMPP_PROD_IBLOCK_ID_LIST'),
			'TYPE'=>'LIST',
			'VALUES'=>$arIBlocks,
			'DEFAULT'=>'1',
			'MULTIPLE'=>'Y',
			'ADDITIONAL_VALUES'=>'N',
			'REFRESH' => 'Y',
		),
		'ACTION_VARIABLE' => array(
			'PARENT' => 'SETTINGS',
			'NAME' => GetMessage('OBXMCMPP_PROD_ACTION_VARIABLE'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'action'
		),
		'PRODUCT_ID_VARIABLE' => array(
			'PARENT' => 'SETTINGS',
			'NAME' => GetMessage('OBXMCMPP_PROD_PRODUCT_ID_VARIABLE'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'prodId'
		),
		'USE_QUANTITY_VARIABLE' => array(
			'PARENT' => 'SETTINGS',
			'NAME' => GetMessage('OBXMCMPP_PROD_USE_QUANTITY_VARIABLE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'REFRESH' => 'Y'
		),
		'PATH_TO_BASKET' => array(
			'PARENT' => 'SETTINGS',
			'NAME' => GetMessage('OBXMCMPP_PROD_PATH_TO_BASKET'),
			'TYPE' => 'STRING',
			'DEFAULT' => '/personal/cart/',
		),

		'SET_TITLE' => array(),
		'CACHE_TYPE' => array(),
		'CACHE_TIME' => array(),

		'TOP_TITLE' => array(
			'PARENT' => 'ADDITIONAL_SETTINGS',
			'NAME' => GetMessage('OBXMCMPP_PROD_PRMGRP_TOP_TITLE'),
			'TYPE' => 'STRING',
			'DEFAULT' => ''
		)
	)
);

foreach($arSelectedIBlockList as $arIBlock) {
	$iblockCode = trim($arIBlock['CODE']);
	if(empty($iblockCode)) {
		$iblockCode = $arIBlock['ID'];
	}
	$arComponentParameters['PARAMETERS']['IBLOCK_PROP_'.$iblockCode] = array(
		'PARENT' => 'IBLOCK_PROPERTIES',
		'NAME' => $arIBlock['NAME'],
		'TYPE' => 'LIST',
		'VALUES' => $arProperties[$arProp['IBLOCK_CODE']],
		'ADDITIONAL_VALUES'=>'Y',
	);
}

