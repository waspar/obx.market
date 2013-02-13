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

class OBX_OrderCommentDBS extends OBX_DBSimple{
	protected $_arTableList = array(
		"OC" => "obx_order_comments"
	);
	protected $_arTableFields = array(
		'ID'							=> array('OC'	=> 'ID'),
		'TIMESTAMP_X'					=> array('OC'	=> 'TIMESTAMP_X'),
		'ORDER_ID'						=> array('OC'	=> 'ORDER_ID'),
		'USER_ID'						=> array('OC'	=> 'USER_ID'),
		//'REPLY_ID'						=> array('OC'	=> 'REPLY_ID'),
		'MESSAGE'						=> array('OC'	=> 'MESSAGE'),
	);
	protected $_mainTable = 'OC';
	protected $_mainTablePrimaryKey = 'ID';
	protected $_mainTableAutoIncrement = 'ID';

	protected $_arTableFieldsDefault = array(
	);
	protected $_arSortDefault = array('TIMESTAMP_X' => 'ASC');

	function __construct(){
		$this->_arTableFieldsCheck = array(
			'ID' 			=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'TIMESTAMP_X'	=> self::FLD_T_NO_CHECK,
			'ORDER_ID'		=> self::FLD_T_INT| self::FLD_NOT_NULL,
			'USER_ID'		=> self::FLD_T_USER_ID| self::FLD_NOT_NULL,
		//	'REPLY_ID'		=> self::FLD_T_INT| self::FLD_NOT_NULL,
			'MESSAGE'		=> self::FLD_T_STRING,
		);
	}
}
class OBX_OrderComment extends OBX_DBSimpleStatic {}
OBX_OrderComment::__initDBSimple(OBX_OrderCommentDBS::getInstance());
?>
