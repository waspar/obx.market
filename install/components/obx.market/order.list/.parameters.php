<?
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if( !CModule::IncludeModule('obx.market') ) {
	return;
}
$rsOrderProps = OBX_OrderPropertyDBS::getInstance()->getList(null, $arPropsFilter);
$arOrderProps = array();
while( ($arProp = $rsOrderProps->Fetch()) ) {
	$arOrderProps[$arProp['ID']] = '['.$arProp['ID'].':'.$arProp['CODE'].'] '.$arProp['NAME'];
}
$arFields = array(
	'STATUS' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_FIELDS_L1'),
	'DATE_CREATED' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_FIELDS_L2'),
	'TIMESTAMP_X' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_FIELDS_L3'),
	'ITEMS_COST' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_FIELDS_L4'),
);

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'SHOW_FIELDS' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_FIELDS'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => $arFields
		),
		'SHOW_PROPS' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_PROPS'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => $arOrderProps
		),
		'SHOW_ITEMS' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('OBX_CMP_ORDER_LIST_PRM_SHOW_ITEMS'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y'
		)
	)
);
?>