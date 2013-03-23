<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ***************************************/

define("OBX_MAGIC_WORD", "I_KNOW_WHAT_I_DO");
define("I_KNOW_WHAT_I_DO", "I_KNOW_WHAT_I_DO");
$arModuleClasses = array(
	'OBX_ECommerceIBlock'				=> 'classes/ECommerceIBlock.php'
   ,'OBX_ECommerceIBlockDBS'			=> 'classes/ECommerceIBlock.php'
   ,'OBX_PriceDBS'						=> 'classes/Price.php'
   ,'OBX_Price'							=> 'classes/Price.php'
   ,'OBX_CIBlockPropertyPriceDBS'		=> 'classes/CIBlockPropertyPrice.php'
   ,'OBX_CIBlockPropertyPrice'			=> 'classes/CIBlockPropertyPrice.php'
   ,'OBX_CurrencyDBS'					=> 'classes/Currency.php'
   ,'OBX_Currency'						=> 'classes/Currency.php'
   ,'OBX_CurrencyFormatDBS'				=> 'classes/CurrencyFormat.php'
   ,'OBX_CurrencyFormat'				=> 'classes/CurrencyFormat.php'
   ,'OBX_Market_BXMainEventsHandlers'	=> 'classes/BXMainEventsHandlers.php'
   ,'OBX_MarketSettings'				=> 'classes/MarketSettings.php'
   ,'OBX_Basket'						=> 'classes/Basket.php'
   ,'OBX_Order'							=> 'classes/Order.php'
   ,'OBX_OrderDBResult'					=> 'classes/OrderDBResult.php'
   ,'OBX_OrderComment'					=> 'classes/OrderComment.php'
   ,'OBX_OrderCommentDBS'				=> 'classes/OrderComment.php'
   ,'OBX_OrdersList'					=> 'classes/OrdersList.php'
   ,'OBX_OrdersListAdminResult'			=> 'classes/OrdersListAdminResult.php'
   ,'OBX_OrderStatus'					=> 'classes/OrderStatus.php'
   ,'OBX_OrderStatusDBS'				=> 'classes/OrderStatus.php'
   ,'OBX_OrderItems'					=> 'classes/OrderItems.php'
   ,'OBX_OrderItemsDBS'					=> 'classes/OrderItems.php'
   ,'OBX_OrderProperty'					=> 'classes/OrderProperty.php'
   ,'OBX_OrderPropertyDBS'				=> 'classes/OrderProperty.php'
   ,'OBX_OrderPropertyEnum'				=> 'classes/OrderPropertyEnum.php'
   ,'OBX_OrderPropertyEnumDBS'			=> 'classes/OrderPropertyEnum.php'
   ,'OBX_OrderPropertyValues'			=> 'classes/OrderPropertyValues.php'
   ,'OBX_OrderPropertyValuesDBS'		=> 'classes/OrderPropertyValues.php'
   ,'OBX_OrderProfile'					=> 'classes/OrderProfile.php'
   ,'OBX_OrdersDBS'						=> 'classes/OrdersList.php'
   ,'OBX_OrdersList'					=> 'classes/OrdersList.php'
);
return $arModuleClasses;
?>
