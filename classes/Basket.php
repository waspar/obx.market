<?php
/***********************************************
 ** @product OBX:Core Bitrix Module           **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

class OBX_Basket extends OBX_CMessagePoolDecorator {

	protected $_VisitorsDBS = null;
	protected $_Visitor = null;
	protected $_BasketItemDBS = null;
	protected $_OrderDBS = null;
	protected $_PriceDBS = null;

	static protected $_arInstances = array();

	/**
	 * @param OBX_Visitor | null $Visitor
	 * @return self
	 */
	final static public function getInstance(OBX_Visitor $Visitor = null) {
		$visitorID = null;
		if($Visitor !== null) {
			$visitorID = $Visitor->getFields('ID');
		}
		$visitorID = intval($visitorID);
		if( $visitorID < 1) {
			$Visitor = new OBX_Visitor();
			$visitorID = $Visitor->getFields('ID');
		}
		$className = get_called_class();
		if( !array_key_exists($className, self::$_arInstances) ) {
			self::$_arInstances[$className] = array();
			if( !array_key_exists($visitorID, self::$_arInstances[$className]) ) {
				self::$_arInstances[$className][$visitorID] = new $className($Visitor);
			}

		}
		return self::$_arInstances[$className][$visitorID];
	}

	protected function __construct(OBX_Visitor &$Visitor) {
		$this->_OrderDBS = OBX_OrderDBS::getInstance();
		$this->_BasketItemDBS = OBX_BasketItemDBS::getInstance();
		$this->_PriceDBS = OBX_PriceDBS::getInstance();
		$this->_Visitor = &$Visitor;
	}
	final protected function __clone() {}
}
