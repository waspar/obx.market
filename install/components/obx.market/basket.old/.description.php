<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

require $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.market/includes/'.LANGUAGE_ID.'/cmp_lang_desc.php';
$arComponentDescription = array(
	'NAME' => GetMessage('OBX_MARKET_BASKET_CMP_NAME'),
	'DESCRIPTION' => GetMessage('OBX_MARKET_BASKET_CMP_DESCRIPTION'),
	'ICON' => '',
	'CACHE_PATH' => 'Y',
	'SORT' => 10,
	'PATH' => array(
		'ID' => 'obx_market',
		'NAME' => GetMessage('OBX_MARKET_CMP_PATH_MARKET_NAME'),
		'CHILD' => array(
			'ID' => 'sale',
			'NAME' => GetMessage('OBX_MARKET_BASKET_CMP_PATH_SALE'),
			'SORT' => 10,
		),
	),
);
