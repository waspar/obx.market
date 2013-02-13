<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ) {

	$OrderStatus = OBX_OrderStatusDBS::getInstance();

	echo '<p>Добавляем три тестовых заказа</p>';
	$arNewOrderID[] = OBX_OrderStatus::add(array(
		'CODE' => 'TEST0',
		'NAME' => 'тестовый статус0',
		'CORLOR' => '00FF00'
	));
	$arNewOrderID[] = OBX_OrderStatus::add(array(
		'CODE' => 'TEST1',
		'NAME' => 'тестовый статус1',
		'CORLOR' => '00FF00'
	));
	$arNewOrderID[] = OBX_OrderStatus::add(array(
		'CODE' => 'TEST2',
		'NAME' => 'тестовый статус2',
		'CORLOR' => '00FF00'
	));
	wd(OBX_OrderStatus::popLastError(), 'OBX_OrderStatus::popLastError()');
	wd(OBX_OrderStatus::getListArray(), 'OBX_OrderStatus::getListArray()');

	echo '<p>Пытаемся добавить хрень в COLOR</p>';
	OBX_OrderStatus::add(array(
		'CODE' => 'INCORR_COLOR',
		'NAME' => 'Неправильный цвет',
		'COLOR' => '0000000000'
	));
	wd(OBX_OrderStatus::popLastError(), 'OBX_OrderStatus::popLastError()');

	echo '<p>Обновляем три тестовых заказа</p>';
	OBX_OrderStatus::Update(array(
		'ID' => $arNewOrderID[0],
		'NAME' => 'Обновленное имя статуса0'
	));
	OBX_OrderStatus::Update(array(
		'ID' => $arNewOrderID[1],
		'NAME' => 'Обновленное имя статуса1'
	));
	OBX_OrderStatus::Update(array(
		'ID' => $arNewOrderID[2],
		'NAME' => 'Обновленное имя статуса2'
	));
	wd(OBX_OrderStatus::popLastError(), 'OBX_OrderStatus::popLastError()');
	wd(OBX_OrderStatus::getListArray(), 'OBX_OrderStatus::getListArray()');

	echo '<p>Удаляем ЧЕТЫРЕ тестовых заказа</p>';
	OBX_OrderStatus::deleteByFilter(array('CODE' => 'TEST0'));
	OBX_OrderStatus::deleteByFilter(array('CODE' => 'TEST1'));
	OBX_OrderStatus::deleteByFilter(array('CODE' => 'TEST2'));
	// этот не существует. Выдаст ошибку
	OBX_OrderStatus::deleteByFilter(array('CODE' => 'TEST3'));
	wd(OBX_OrderStatus::popLastError(), 'OBX_OrderStatus::popLastError()');

}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>