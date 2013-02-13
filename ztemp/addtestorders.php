<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ) {
	if( isset($_GET['user_id']) ) {
		$USER_ID = intval($_GET['user_id']);
	}
	for($i=0; $i<20; $i++) {
		$NewOrder = OBX_Order::add(array(
			'USER_ID' => $USER_ID
		));
		$NewOrder->setProperties(array(
			'IS_PAID' => 'N',
			'DELIVERY' => '1',
			'PAYMENT' => '1'
		));
		$NewOrder->setItems(array(
			array(
				'PRODUCT_ID' => 100,
				'QUANTITY' => 3,
			),
			array(
				'PRODUCT_ID' => 101,
				'QUANTITY' => 3,
			),
			array(
				'PRODUCT_ID' => 102,
				'QUANTITY' => 3,
			),
			array(
				'PRODUCT_ID' => 167,
				'QUANTITY' => 3,
			),
			array(
				'PRODUCT_ID' => 168,
				'QUANTITY' => 3,
			),
			array(
				'PRODUCT_ID' => 169,
				'QUANTITY' => 3,
			),
			array(
				'PRODUCT_ID' => 170,
				'QUANTITY' => 3,
			)
		));
		wd($NewOrder->getErrors(), '$NewOrder->getErrors()');
	}

}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>