<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

final class OBX_Test_OrderPropsJsonSubQuery extends OBX_Market_TestCase {

	protected function getJsonErrorText() {
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$strError = ' - Ошибок нет';
				break;
			case JSON_ERROR_DEPTH:
				$strError = ' - Достигнута максимальная глубина стека';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$strError = ' - Некорректные разряды или не совпадение режимов';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$strError = ' - Некорректный управляющий символ';
				break;
			case JSON_ERROR_SYNTAX:
				$strError = ' - Синтаксическая ошибка, не корректный JSON';
				break;
			case JSON_ERROR_UTF8:
				$strError = ' - Некорректные символы UTF-8, возможно неверная кодировка';
				break;
			default:
				$strError = ' - Неизвестная ошибка';
				break;
		}
		return $strError;
	}

	public function testSQL() {
		global $DB;
		$rs = $DB->Query(<<<SQL
SELECT
	 O.ID as ID
	,O.DATE_CREATED as DATE_CREATED
	,O.ID as USER_ID
	,U.NAME as USER_NAME
	,O.STATUS_ID as STATUS_ID
	,S.CODE as STATUS_CODE
	,S.NAME as STATUS_NAME
	,O.CURRENCY as CURRENCY
	,(
		GROUP_CONCAT(CONCAT("[",I.ID,"]"," ",I.PRODUCT_NAME," - ",I.QUANTITY) SEPARATOR "\n")
	) as ITEMS
	,(
		SUM(I.PRICE_VALUE * I.QUANTITY)
	) as ITEMS_COST
	,(
		SELECT
			concat(
				'[',
				group_concat(
					concat('{ "PROPERTY_ID": "', OP.ID, '"'),
					concat(', "PROPERTY_TYPE": "', OP.PROPERTY_TYPE, '"'),
					concat(', "PROPERTY_NAME": "', OP.NAME, '"'),
					concat(', "PROPERTY_CODE": "', OP.CODE, '" }')
				),
				']'
			)
		FROM
			obx_order_property as OP
		LEFT JOIN
			obx_order_property_values as OPV ON (OPV.PROPERTY_ID = OP.ID)
		WHERE
			OPV.ORDER_ID = O.ID
		GROUP BY
			OPV.ORDER_ID
	) as PROPERTIES_JSON
FROM obx_orders as O
LEFT JOIN obx_order_status as S ON (O.STATUS_ID = S.ID)
LEFT JOIN obx_basket_items as I ON (O.ID = I.ORDER_ID)
LEFT JOIN b_user as U ON (O.USER_ID = U.ID)

WHERE O.ID = 2;
SQL
);
		$arResult = $rs->Fetch();
		$this->assertTrue(is_array($arResult));
		$this->assertArrayHasKey('PROPERTIES_JSON', $arResult);
		$arJSON = json_decode($arResult['PROPERTIES_JSON'], true);
		if($arJSON == false) {
			$strError = $this->getJsonErrorText();
			$this->assertTrue(is_array($arJSON), 'Error: '.$strError);
			$this->assertArrayHasKey('PROPERTY_ID', $arJSON);
			$this->assertArrayHasKey('PROPERTY_TYPE', $arJSON);
			$this->assertArrayHasKey('PROPERTY_NAME', $arJSON);
			$this->assertArrayHasKey('PROPERTY_CODE', $arJSON);
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_ID'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_TYPE'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_NAME'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_CODE'])));
		}
	}

	public function testGetList() {
		$OrderList = OBX_OrderDBS::getInstance();
		$arResult = $OrderList->getListArray(null, array('ID' => 2), null, null, array(
			'ID',
			'DATE_CREATED',
			'USER_ID',
			'USER_NAME',
			'STATUS_ID',
			'STATUS_CODE',
			'STATUS_NAME',
			'CURRENCY',
			'ITEMS',
			'ITEMS_COST',
			'PROPERTIES_JSON'
		));
		$arResult = $arResult[0];
		$this->assertTrue(is_array($arResult));
		$this->assertArrayHasKey('PROPERTIES_JSON', $arResult);
		$arJSON = json_decode($arResult['PROPERTIES_JSON'], true);
		if($arJSON == false) {
			$strError = $this->getJsonErrorText();
			$this->assertTrue(is_array($arJSON), 'Error: '.$strError);
			$this->assertArrayHasKey('PROPERTY_ID', $arJSON);
			$this->assertArrayHasKey('PROPERTY_TYPE', $arJSON);
			$this->assertArrayHasKey('PROPERTY_NAME', $arJSON);
			$this->assertArrayHasKey('PROPERTY_CODE', $arJSON);
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_ID'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_TYPE'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_NAME'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_CODE'])));
		}
	}

	public function testGetByID() {
		$OrderList = OBX_OrderDBS::getInstance();
		$arResult = $OrderList->getByID(2, array(
			'ID',
			'DATE_CREATED',
			'USER_ID',
			'USER_NAME',
			'STATUS_ID',
			'STATUS_CODE',
			'STATUS_NAME',
			'CURRENCY',
			'ITEMS',
			'ITEMS_COST',
			'PROPERTIES_JSON'
		));
		$arResult = $arResult[0];
		$this->assertTrue(is_array($arResult));
		$this->assertArrayHasKey('PROPERTIES_JSON', $arResult);
		$arJSON = json_decode($arResult['PROPERTIES_JSON'], true);
		if($arJSON == false) {
			$strError = $this->getJsonErrorText();
			$this->assertTrue(is_array($arJSON), 'Error: '.$strError);
			$this->assertArrayHasKey('PROPERTY_ID', $arJSON);
			$this->assertArrayHasKey('PROPERTY_TYPE', $arJSON);
			$this->assertArrayHasKey('PROPERTY_NAME', $arJSON);
			$this->assertArrayHasKey('PROPERTY_CODE', $arJSON);
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_ID'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_TYPE'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_NAME'])));
			$this->assertGreaterThan(0, strlen(trim($arJSON['PROPERTY_CODE'])));
		}
	}
}