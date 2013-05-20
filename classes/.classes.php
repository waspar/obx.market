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
	 'OBX_Market_BXMainEventsHandlers'			=> 'classes/BXMainEventsHandlers.php'
	,'OBX\Market\Settings'						=> 'classes/MarketSettings.php'
	,'OBX_MarketSettings'						=> 'classes/MarketSettings_.php'
	,'OBX\Market\ECommerceIBlock'				=> 'classes/ECommerceIBlock.php'
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
	,'OBX\Market\BasketDBS'						=> 'classes/BasketList.php'
	,'OBX\Market\BasketList'					=> 'classes/BasketList.php'
	,'OBX\Market\BasketItemDBS'					=> 'classes/BasketItem.php'
	,'OBX\Market\BasketItem'					=> 'classes/BasketITem.php'
	,'OBX\Market\Basket'						=> 'classes/Basket.php'
	,'OBX_BasketOld'							=> 'classes/Basket.old.php'
	,'OBX\Market\OrderDBS'						=> 'classes/OrderList.php'
	,'OBX\Market\OrderList'						=> 'classes/OrderList.php'
	,'OBX\Market\OrderDBResult'					=> 'classes/OrderDBResult.php'
	,'OBX\Market\Order'							=> 'classes/Order.php'
	,'OBX\Market\OrderStatus'					=> 'classes/OrderStatus.php'
	,'OBX\Market\OrderStatusDBS'				=> 'classes/OrderStatus.php'
	,'OBX\Market\OrderProperty'					=> 'classes/OrderProperty.php'
	,'OBX\Market\OrderPropertyDBS'				=> 'classes/OrderProperty.php'
	,'OBX\Market\OrderPropertyEnum'				=> 'classes/OrderPropertyEnum.php'
	,'OBX\Market\OrderPropertyEnumDBS'			=> 'classes/OrderPropertyEnum.php'
	,'OBX\Market\OrderPropertyValues'			=> 'classes/OrderPropertyValues.php'
	,'OBX\Market\OrderPropertyValuesDBS'		=> 'classes/OrderPropertyValues.php'
	,'OBX\Market\OrdersListAdminResult'			=> 'classes/OrdersListAdminResult.php'
	,'OBX\Market\OrderProfile'					=> 'classes/OrderProfile.php'

);
return $arModuleClasses;
