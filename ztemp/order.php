<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
?>
<? if (CModule::IncludeModule("obx.market") && $USER->IsAdmin()): ?>
<?

	//test orderItems
	$arOrderItemsFields = array("ORDER_ID" => 1, "PRODUCT_ID" => 1);
	$OrderItems = OBX_OrderItemsDBS::getInstance();
	$OrderItems->add($arOrderItemsFields);

	//debug
	//$OrderItems->
	//GetList
	$orderRes = OBX_Order::getList();
	wd($orderRes, '::getList()');
	/*
	while ($cur = $OrderRes -> getNextOrder()){
		wd($cur,"order");
	}
	*/
	//GetListArray
	$arOrders = OBX_Order::getListArray();
	wd($arOrders, '::getListArray()');
	//getByID
	$arOrder = OBX_Order::getByID(1);
	wd($arOrder, '::getByID()');
	//ad()
	$NewOrder = OBX_Order::add();
	wd($NewOrder, '::add()');

	//->setStatusID
	$NewOrder->setStatus(2);
	wd($NewOrder->getFields(), "ChangeStatusFromID");
	//->setStatusCODE
	$NewOrder->setStatus("COMPLETE");
	wd($NewOrder->getFields(), "ChangeStatusFromCODE");
	//->setProperties
	wd($NewOrder->setProperties(
		array(
			"ADDRESS" => "г.Красноярск ул.Метталургов 2в \n офис 220",
			"NONE" => 123456
		)
	), "->setProperties()");
	//->getProperties
	wd($NewOrder->getProperties(), "->getProperties");
	//->setItems
	echo "add new order items<br />\n";
	$NewOrder->setItems(array(
		array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 1,
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 2
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 3
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 4
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 5
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 6
		)
	));
	wd($NewOrder->getItems(), '$NewOrder->getItems()');
	echo "update order items quantity<br />\n";
	$NewOrder->setItems(array(
		array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 1,
			'QUANTITY' => 3,
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 2,
			'QUANTITY' => 3,
		)
	, array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 3,
			'QUANTITY' => 3,
			'QUANTITY_ADD' => 'Y'
		)
	));
	wd($NewOrder->getItems(), '$NewOrder->getItems()');
	echo "hard set of the order items list<br />\n";
	$NewOrder->setItems(array(
		array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 1,
			'QUANTITY' => 3,
		),
		array(
			'IBLOCK_ID' => 1,
			'PRODUCT_ID' => 5,
			'QUANTITY' => 3,
		)
	), true);
	wd($NewOrder->getItems(), '$NewOrder->getItems()');

	//delete()
	$arNewOrder = $NewOrder->getFields();
	$NewOrderID = $arNewOrder["ID"];
	wd($NewOrder->delete($NewOrderID), '::delete()');
endif?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>


<?/*
<?$APPLICATION->IncludeComponent(
	"A68:searching.4.programmer",
	".default", array(
		"PHONE" => "222-22-22",
		"ADDRESS" => "Металлургов 2в."
	)
)?>
*/?>