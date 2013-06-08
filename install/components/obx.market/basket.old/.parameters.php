<?
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @license Affero GPLv3             **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
if( !CModule::IncludeModule('obx.market') ) {
	return;
}
$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'BASKET_ID' => array(
			'NAME' => GetMessage('OBX_MARKET_CMP_BASKET_BASKET_ID'),
			'TYPE' => 'TEXT',
			'DEFAULT' => OBX_Basket::defaultBasketID
		)
	)
);
?>