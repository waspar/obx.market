<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetPageProperty("__hide_footer", "Y");
?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?
	$arPropsList = OBX_OrderProperty::getList();
	wd($arPropsList, '$arPropsList');

	$arFIO = OBX_OrderProperty::getByCode("FIO");
	wd($arFIO, '$arFIO');

	$newPropID = OBX_OrderProperty::add(array(
		"NAME" => "Тестовое имя",
		"CODE" => "TEST_PROP_CODE",
		"PROPERTY_TYPE" => "S",
		"SORT" => "100",
		"ACTIVE" => "Y"
	));
	if($newPropID) {
		?>Свойство успешно создано <br /><?
		$arTestOrderProp = OBX_OrderProperty::getByID($newPropID);
		wd($arTestOrderProp, '$arTestOrderProp');
		if( true && OBX_OrderProperty::delete($arTestOrderProp["ID"]) ) {
			?>Тестовое свойство успешно удалено.<br /><?
			wd($arTestOrderProp, '$arTestOrderProp');
		}
	}
	if(true) {
		$rsTestOrderProp = OBX_OrderProperty::getByCode("TEST_PROP_CODE", false);
		while($arTestOrderProp = $rsTestOrderProp->GetNext()) {
			if( OBX_OrderProperty::delete($arTestOrderProp["ID"]) ) {
				?>Тестовое свойство успешно удалено.<br /><?
				wd($arTestProp, '$arTestProp');
			}
		}
	}

?>
<?endif?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>