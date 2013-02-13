<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?

	$OrderPropValuesDBS = OBX_OrderPropertyValuesDBS::getInstance();
	$OrderPropValuesDBS->add(array(
		'ORDER_ID' => 1,
		'PROPERTY_ID' => 1,
		'VALUE' => 'Петя'
	));
	$OrderPropValuesDBS->add(array(
		'ORDER_ID' => 1,
		'PROPERTY_ID' => 2,
		'VALUE' => 'ул. им. Линуса Торвальдса, порт 7, пакет 5'
	));
	$OrderPropValuesDBS->add(array(
		'ORDER_ID' => 1,
		'PROPERTY_ID' => 3,
		'VALUE' => 1
	));

?>
<?endif?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>