<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ) {
	$arNavParams = array('nPageSize' => 4);
	$arNavigation = CDBResult::GetNavParams($arNavParams);

	//$arNavStartParams = ;
	$Orders = OBX_Order::getList(null,null,null,$arNavParams);

	wd($arNavigation,'$arNavigation');

	while( $arOrder = $Orders->Fetch()){
		wd($arOrder,"arOrder:".$arOrder["ID"]);
	}
//	echo '<p>Создаём пару новых заказов</p>';
//	$arOrderID[] = OBX_OrdersList::add(array(
//		'USER_ID' => $USER->GetID(),
//		'STATUS_ID' => '1',
//		'CURRENCY' => 'RUB',
//	));
//	$arOrderID[] = OBX_OrdersList::add(array(
//		'USER_ID' => $USER->GetID(),
//		'STATUS_ID' => '1',
//		'CURRENCY' => 'RUB',
//	));
//	wd($arOrderID, '$arOrderID');
//	wd(OBX_OrdersList::popLastError(), 'OBX_OrdersList::popLastError()');
//	wd(OBX_OrdersList::getListArray(), 'OBX_OrdersList::getListArray()');
//
//	echo '<p>Добавляем товры к заказу</p>';
//	foreach($arOrderID as $orderID) {
//		OBX_OrderItems::add(array(
//			'ORDER_ID' => $orderID,
//			'IBLOCK_ID' => '1',
//			'PRODUCT_ID' => '1',
//			'PRODUCT_NAME' => 'Товар номер один',
//		));
//		OBX_OrderItems::add(array(
//			'ORDER_ID' => $orderID,
//			'IBLOCK_ID' => '1',
//			'PRODUCT_ID' => '2',
//			'PRODUCT_NAME' => 'Товар номер один',
//		));
//	}
//	wd(OBX_OrderItems::popLastError(), 'OBX_OrderItems::popLastError()');
//	wd(OBX_OrderItems::getListArray(), 'OBX_OrderItems::getListArray()');
//
//	echo '<p>Удаляем созданные заказы</p>';
//	foreach($arOrderID as $orderID) {
//		OBX_OrdersList::delete($orderID);
//	}
//	wd(OBX_OrdersList::popLastError(), 'OBX_OrdersList::popLastError()');
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>