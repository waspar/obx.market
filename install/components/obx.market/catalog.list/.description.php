<?php
/*******************************************
 ** @product OBX:Market Bitrix Module     **
 ** @authors                              **
 **         Maksim S. Makarov aka pr0n1x  **
 **         Morozov P. Artem aka tashiro  **
 ** @license Affero GPLv3                 **
 ** @mailto rootfavell@gmail.com          **
 ** @mailto tashiro@yandex.ru             **
 ** @copyright 2013 DevTop                **
 *******************************************/
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
require $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.market/includes/'.LANGUAGE_ID.'/cmp_lang_desc.php';
$arComponentDescription = array(
	'NAME' => GetMessage('OBX_MARKET_CATALOG_LIST_CMP_NAME'),
	'DESCRIPTION' => GetMessage('OBX_MARKET_CATALOG_LIST_CMP_DESCRIPTION'),
	'ICON',
	'CACHE_PATH' => 'Y',
	'SORT' => 10,
	'PATH' => array(
		'ID' => 'obx_market',
		'NAME' => GetMessage('OBX_MARKET_CMP_PATH_MARKET_NAME'),
		'CHILD' => array(
			'ID' => 'obx_catalog',
			'NAME' => GetMessage('OBX_MARKET_BASKET_CMP_PATH_CATALOG'),
			'SORT' => 10,
		),
	),
);
?>