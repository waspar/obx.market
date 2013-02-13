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
IncludeModuleLangFile(__FILE__);

class OBX_OrderDBResult extends CDBResult {

	function __construct($DBResult = null) {
		parent::__construct($DBResult);
	}

	function getNextOrder() {
		return OBX_Order::getOrder($this);
	}
}
?>