<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

IncludeModuleLangFile(__FILE__);

class OBX_OrderDBResult extends OBX_DBSResult {

	function __construct($DBResult = null) {
		parent::__construct($DBResult);
	}

	function getNextOrder() {
		return OBX_Order::getOrder($this);
	}
}
