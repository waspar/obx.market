<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

IncludeModuleLangFile(__FILE__);

class OrderDBResult extends \OBX_DBSResult {

	function __construct($DBResult = null) {
		parent::__construct($DBResult);
	}

	function getNextOrder() {
		return Order::getOrder($this);
	}
}
