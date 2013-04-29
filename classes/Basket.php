<?php
/***********************************************
 ** @product OBX:Core Bitrix Module           **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

class OBX_BasketDBS extends OBX_DBSimple {
	protected $_arTableFields = array(

	);
	function __construct() {
		$this->_arTableFieldsCheck = array(

		);
	}
}

class OBX_Basket extends OBX_CMessagePoolDecorator {

}