<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 **         Artem P. Morozov  aka tashiro     **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

$arModuleClasses = array(
	 'OBX\Market\ECommerceIBlock'				=> 'classes/ECommerceIBlock.php'
	,'OBX\Market\ECommerceIBlockDBS'			=> 'classes/ECommerceIBlock.php'
	,'OBX\Market\PriceDBS'						=> 'classes/Price.php'
	,'OBX\Market\Price'							=> 'classes/Price.php'
	,'OBX\Market\CIBlockPropertyPriceDBS'		=> 'classes/CIBlockPropertyPrice.php'
	,'OBX\Market\CIBlockPropertyPrice'			=> 'classes/CIBlockPropertyPrice.php'
	,'OBX\Market\CurrencyDBS'					=> 'classes/Currency.php'
	,'OBX\Market\Currency'						=> 'classes/Currency.php'
	,'OBX\Market\CurrencyFormatDBS'				=> 'classes/CurrencyFormat.php'
	,'OBX\Market\CurrencyFormat'				=> 'classes/CurrencyFormat.php'
	,'OBX\Market\CurrencyInfo'					=> 'classes/CurrencyInfo.php'
	,'OBX_Market_BXMainEventsHandlers'			=> 'classes/BXMainEventsHandlers.php'
	,'OBX_MarketSettings'						=> 'classes/MarketSettings.php'
	,'OBX\Market\BasketDBS'						=> 'classes/BasketList.php'
	,'OBX\Market\BasketList'					=> 'classes/BasketList.php'
	,'OBX\Market\BasketItemDBS'					=> 'classes/BasketItem.php'
	,'OBX\Market\BasketItem'					=> 'classes/BasketITem.php'
	,'OBX\Market\Basket'						=> 'classes/Basket.php'
	,'OBX_BasketOld'							=> 'classes/Basket.old.php'
	,'OBX_Order'								=> 'classes/Order.php'
	,'OBX_OrderDBResult'						=> 'classes/OrderDBResult.php'
	,'OBX_OrderComment'							=> 'classes/OrderComment.php'
	,'OBX_OrderCommentDBS'						=> 'classes/OrderComment.php'
	,'OBX_OrdersListAdminResult'				=> 'classes/OrdersListAdminResult.php'
	,'OBX_OrderStatus'							=> 'classes/OrderStatus.php'
	,'OBX_OrderStatusDBS'						=> 'classes/OrderStatus.php'
	,'OBX_OrderProperty'						=> 'classes/OrderProperty.php'
	,'OBX_OrderPropertyDBS'						=> 'classes/OrderProperty.php'
	,'OBX_OrderPropertyEnum'					=> 'classes/OrderPropertyEnum.php'
	,'OBX_OrderPropertyEnumDBS'					=> 'classes/OrderPropertyEnum.php'
	,'OBX_OrderPropertyValues'					=> 'classes/OrderPropertyValues.php'
	,'OBX_OrderPropertyValuesDBS'				=> 'classes/OrderPropertyValues.php'
	,'OBX_OrderProfile'							=> 'classes/OrderProfile.php'
	,'OBX_OrderDBS'								=> 'classes/OrderList.php'
	,'OBX_OrderList'							=> 'classes/OrderList.php'
);
return $arModuleClasses;
