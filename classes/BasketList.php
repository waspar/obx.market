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

class OBX_BasketDBS extends OBX_DBSimple
{
	protected $_mainTable = 'B';
	protected $_arTableList = array(
		'B'		=> 'obx_basket',
		'BI'	=> 'obx_basket_items'
	);
	protected $_arTableFields = array(
		'ID'				=> array('B' => 'ID'),
		'ORDER_ID'			=> array('B' => 'ORDER_ID'),
		'USER_ID'			=> array('B' => 'USER_ID'),
		'HASH'				=> array('B' => 'HASH'),
		'ITEMS_JSON' => array('BI' => '
				concat(
					\'{ \',
						\'"items": [\',
							group_concat(
								concat(\'{ \',
											\'"ID": "\',				BI.ID,				\'", \',
											\'"PRODUCT_ID": "\',		BI.PRODUCT_ID,		\'", \',
											\'"PRODUCT_NAME": "\',		BI.PRODUCT_NAME,	\'", \',
											\'"QUANTITY": "\',			BI.QUANTITY,		\'", \',
											\'"PRICE_VALUE": "\',		BI.PRICE_VALUE,		\'"\',
									\'" }\'
								)
							),
						\'], \',
						\'"cost": "\', SUM(BI.PRICE_VALUE * BI.QUANTITY) ,\'"\'
					\' }\'
				)'
		),
		'ITEMS_COST' => array('BI' => 'SUM(BI.PRICE_VALUE * BI.QUANTITY)'),
	);
	protected $_arTableLeftJoin = array(
		'BI' => 'B.ID = BI.BASKET_ID'
	);
	protected $_arGroupByFields = array(
		'B' => 'ID'
	);

	protected $_arSelectDefault = array(
		'ID', 'ORDER_ID', 'USER_ID', 'HASH'
	);

	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID' => self::FLD_T_INT | self::FLD_NOT_NULL,
			'ORDER_ID' => self::FLD_T_INT | self::FLD_NOT_NULL | self::FLD_CUSTOM_CK,
			'USER_ID' => self::FLD_T_USER_ID | self::FLD_NOT_NULL,
			'HASH' => self::FLD_T_IDENT | self::FLD_CUSTOM_CK
		);
	}

	public function __check_HASH(&$value, &$arCheckData = null) {
		if(
			! is_string($value)
			||
			! preg_match('~[a-f0-9]{32}~', $value)
		) {
			if($arCheckData !== null) {
				$this->addError(GetMessage('OBX_VISITORS_ERROR_WRONG_COOKIE_ID', 7));
			}
			return false;
		}
		return true;
	}
}