<?php

use OBX\Core\Tools;
use OBX\Market\Basket;
use OBX\Market\BasketList;
use OBX\Market\Price;
use OBX\Market\CurrencyFormatDBS;
use OBX\Market\Order;
use OBX\Market\OrderList;

//Заголовки для предотвращения кеширования и указания типа данных JSON
header('Cache-Control: no-cache, must-revalidate');

header('Content-type: application/json');

/*
 *************** ORDER FIELDS ***************
 *
 *
 * 'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
 * 'DATE_CREATED' => self::FLD_T_NO_CHECK,
 * 'TIMESTAMP_X' => self::FLD_T_NO_CHECK,
 * 'USER_ID' => self::FLD_T_USER_ID | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
 * 'STATUS_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_DEFAULT | self::FLD_REQUIRED,
 * 'DELIVERY_ID' => self::FLD_T_INT,
 * 'DELIVERY_COST' => self::FLD_T_FLOAT,
 * 'PAY_ID' => self::FLD_T_INT,
 * 'PAY_TAX_VALUE' => self::FLD_T_FLOAT,
 * 'DISCOUNT_ID' => self::FLD_T_INT,
 * 'DISCOUNT_VALUE' => self::FLD_T_FLOAT
 */


require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
IncludeModuleLangFile(__FILE__);

$arJSON = array(
	'messages' => array()
);

if (!CModule::IncludeModule('obx.market')) {
	$arJSON['messages'][] = array(
		'TYPE' => 'E',
		'TEXT' => GetMessage('OBX_MARKET_MODULE_NOT_INSTALLED'),
		'CODE' => 1
	);
}
else {

	if (!empty($_REQUEST["MAKE_ORDER"])) {

		if (empty($_REQUEST["PHONE"])) {
			$arJSON['success'] = "N";
			$arJSON['messages'][] = GetMessage('OBX_MARKET_AJAX_ORDER_ERROR_1');
			echo json_encode($arJSON);
			return false;
		}
		$CurrentBasket = Basket::getCurrent();

		$arAddOrderErrors = array();
		$NewOrder = Order::add(array("USER_ID" => $CurrentBasket->getFields("USER_ID")), $arAddOrderErrors);
		if ($NewOrder == null) {
			$arJSON['success'] = "N";
			foreach($arAddOrderErrors as $arAddOrderErrorItem) {
				$arJSON['messages'][] = $arAddOrderErrorItem['TEXT'];
			}
		}
		else {
			$phone = preg_replace('~[^\d]~', '', $_REQUEST["PHONE"]);

			$arProps = array(
				"PHONE" => $phone
			);

			$NewOrder->setProperties($arProps);
			$newOrderID = $NewOrder->getID();

			$OrderBasket = Basket::getByOrderID($newOrderID);

			$OrderBasket->mergeBasket($CurrentBasket, true);
			unset($CurrentBasket);

			if ($OrderBasket->getLastError() == null) {
				$arJSON['success'] = "Y";
			} else {
				$arJSON['success'] = "N";
				$arJSON['messages'][] = $OrderBasket->getLastError();
			}
		}
	}
}


//print_r($arJSON);
echo json_encode($arJSON);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
