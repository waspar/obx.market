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
	 'OBX_ECommerceIBlock'				=> 'classes/ECommerceIBlock.php'
	,'OBX_ECommerceIBlockDBS'			=> 'classes/ECommerceIBlock.php'
	,'OBX\PriceDBS'						=> 'classes/Price.php'
	,'OBX\Price'						=> 'classes/Price.php'
	,'OBX_CIBlockPropertyPriceDBS'		=> 'classes/CIBlockPropertyPrice.php'
	,'OBX_CIBlockPropertyPrice'			=> 'classes/CIBlockPropertyPrice.php'
	,'OBX\CurrencyDBS'					=> 'classes/Currency.php'
	,'OBX\Currency'						=> 'classes/Currency.php'
	,'OBX\CurrencyFormatDBS'			=> 'classes/CurrencyFormat.php'
	,'OBX\CurrencyFormat'				=> 'classes/CurrencyFormat.php'
	,'OBX\CurrencyInfo'					=> 'classes/CurrencyInfo.php'
	,'OBX_Market_BXMainEventsHandlers'	=> 'classes/BXMainEventsHandlers.php'
	,'OBX_MarketSettings'				=> 'classes/MarketSettings.php'
	,'OBX_BasketDBS'					=> 'classes/BasketList.php'
	,'OBX_BasketList'					=> 'classes/BasketList.php'
	,'OBX_BasketItemDBS'				=> 'classes/BasketItem.php'
	,'OBX_BasketItem'					=> 'classes/BasketITem.php'
	,'OBX_Basket'						=> 'classes/Basket.php'
	,'OBX_Order'						=> 'classes/Order.php'
	,'OBX_OrderDBResult'				=> 'classes/OrderDBResult.php'
	,'OBX_OrderComment'					=> 'classes/OrderComment.php'
	,'OBX_OrderCommentDBS'				=> 'classes/OrderComment.php'
	,'OBX_OrdersListAdminResult'		=> 'classes/OrdersListAdminResult.php'
	,'OBX_OrderStatus'					=> 'classes/OrderStatus.php'
	,'OBX_OrderStatusDBS'				=> 'classes/OrderStatus.php'
	,'OBX_OrderProperty'				=> 'classes/OrderProperty.php'
	,'OBX_OrderPropertyDBS'				=> 'classes/OrderProperty.php'
	,'OBX_OrderPropertyEnum'			=> 'classes/OrderPropertyEnum.php'
	,'OBX_OrderPropertyEnumDBS'			=> 'classes/OrderPropertyEnum.php'
	,'OBX_OrderPropertyValues'			=> 'classes/OrderPropertyValues.php'
	,'OBX_OrderPropertyValuesDBS'		=> 'classes/OrderPropertyValues.php'
	,'OBX_OrderProfile'					=> 'classes/OrderProfile.php'
	,'OBX_OrderDBS'						=> 'classes/OrderList.php'
	,'OBX_OrderList'					=> 'classes/OrderList.php'

	// compat
	,'OBX_Currency'						=> 'classes/.compat.php'
	,'OBX_CurrencyDBS'					=> 'classes/.compat.php'
	,'OBX_CurrencyFormat'				=> 'classes/.compat.php'
	,'OBX_CurrencyFormatDBS'			=> 'classes/.compat.php'
	,'OBX_CurrencyInfo'					=> 'classes/.compat.php'
	,'OBX_Price'						=> 'classes/.compat.php'
	,'OBX_PriceDBS'						=> 'classes/.compat.php'
);
return $arModuleClasses;
