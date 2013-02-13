<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?
	$arOrderStatusList = OBX_OrderStatus::getList();

	?><p>Полная выборка статусов</p><?
	wd($arOrderStatusList, 'OBX_OrderStatus::getList(): $arOrderStatusList');

	?><p>Выборка по коду</p><?
	wd(OBX_OrderStatus::getByCode("accepted"), 'OBX_OrderStatus::getByCode("accepted")');

	?><p>Выборка по ИД</p><?
	wd(OBX_OrderStatus::getByID("2"), 'OBX_OrderStatus::getByID("2")');

	?><p>Тест создания и удалегния свойств</p><?
	for($i=0; $i < 3; $i++) {
		$arNewOrderStatusFields = array();
		if($i>0) {
			$arNewOrderStatusFields["CODE"] = "status_test";
		}
		if($i>1) {
			$arNewOrderStatusFields["NAME"] = "Тестовый статус";
		}
		wd($arNewOrderStatusFields, '$arNewOrderStatusFields');
		$statusID = OBX_OrderStatus::add($arNewOrderStatusFields);
		if($statusID) {
			?>Статус успешно добавлен <br /><?
			wd(OBX_OrderStatus::getByID($statusID), 'OBX_OrderStatus::getByID($statusID)');
			?>Попытка добавления заказа с существующим Символьным кодом<?
			$existsStatusID = OBX_OrderStatus::add($arNewOrderStatusFields);
			if(!$existsStatusID) {
				wd(OBX_OrderStatus::getLastError(), 'OBX_OrderStatus::getLastError()');
			}

			$bDeleted = OBX_OrderStatus::delete($statusID);
			if($bDeleted) {
				?>Статус (ID: <?=$statusID?>) успешно удалён<?
			}
			else {
				?>Ошибка удаления статуса (ID: <?=$statusID?>)<?
				wd(OBX_OrderStatus::getLastError(), 'OBX_OrderStatus::getLastError()');
			}
		}
		else {
			?>Не удалось добавить статус: <? echo OBX_OrderStatus::getLastError();?><br /><?
		}
	}

	?><p>Тест обновления статуса заказа</p><?
	$arUpdateOrderStatusFields = array(
		"NAME" => "Тестовый статус обновления",
		"CODE" => "test_update_status",
		"SORT" => 1000,
		"IS_DEFAULT" => "N"
	);


	if( !($arExistsStatus = OBX_OrderStatus::getByCode($arUpdateOrderStatusFields["CODE"])) ) {
		$updateStatusID = OBX_OrderStatus::add($arUpdateOrderStatusFields);
		if($updateStatusID) {
			?>Статус заказа для теста обновления успешно создан (ID: <?=$updateStatusID?>)<br /><?
		}
		else {
			?>Ошибка создания статуса заказа:<?
			wd(OBX_OrderStatus::getLastError(), 'OBX_OrderStatus::getLastError()');
		}
	}
	else {
		$updateStatusID = $arExistsStatus["ID"];
	}
	if($updateStatusID) {

		?>Заказ до:<br /><?
		wd(OBX_OrderStatus::getByID($updateStatusID), 'OBX_OrderStatus::getByID('.$updateStatusID.')');

		?>Попытка обновить код статуса на код, который имеет другой статус<br /><?
		$arUpdateOrderStatusFields["ID"] = $updateStatusID;
		$arUpdateOrderStatusFields["CODE"] = "accepted";
		if( !OBX_OrderStatus::update($arUpdateOrderStatusFields) ) {
			wd(OBX_OrderStatus::getLastError(), 'OBX_OrderStatus::getLastError()');
		}

		?>Обновляем стутус:<br /><?
		$arUpdateOrderStatusFields["NAME"] = "Новое имя";
		$arUpdateOrderStatusFields["CODE"] = "test_update_status1";
		$arUpdateOrderStatusFields["SORT"] = 123;
		$arUpdateOrderStatusFields["IS_DEFAULT"] = "Y";
		wd($arUpdateOrderStatusFields, '$arUpdateOrderStatusFields');
		if( !OBX_OrderStatus::update($arUpdateOrderStatusFields) ) {
			wd(OBX_OrderStatus::getLastError(), 'OBX_OrderStatus::getLastError()');
		}

		?>Статус после:<br /><?
		wd(OBX_OrderStatus::getByID($updateStatusID), 'OBX_OrderStatus::getByID('.$updateStatusID.')');

		?>Удаление статуса<br /><?
		if( OBX_OrderStatus::delete($updateStatusID) ) {
			?>Статус (ID: <?=$updateStatusID?>) успешно удялен<?
		}
	}
?>
<?endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>