<?
/*****************************************
 ** @vendor A68 Studio                  **
 ** @mailto info@a-68.ru                **
 ** @time 16:18                       **
 ** @user tashiro                       **
 *****************************************/
class OBX_BXAdminUtils extends OBX_CMessagePool{

	protected $_arChain = array();

	function __construct() {
		$this->_arChain = array(
			"obx_market" => array(
				"TEXT" => GetMessage("OBX_MARKET_NAME"),
				"LINK" => "obx_market_index.php",
				"DEPTH" => 0,
				"PARENT" => null,
				"IS_PARENT" => true),
			"obx_market_orders" => array(
				"TEXT" => GetMessage("OBX_MARKET_ORDERS"),
				"LINK" => "obx_market_orders.php",
				"DEPTH" => 1,

			),
		);
	}


	public static function setChain() {
		
	}
}
?>
