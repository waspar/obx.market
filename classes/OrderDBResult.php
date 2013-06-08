<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

use OBX\Core\Tools;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;
use OBX\Core\DBSResult;

IncludeModuleLangFile(__FILE__);

class OrderDBResult extends DBSResult {

	function __construct($DBResult = null) {
		parent::__construct($DBResult);
	}

	function getNextOrder() {
		return Order::getOrder($this);
	}
}
