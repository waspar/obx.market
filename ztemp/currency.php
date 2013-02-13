<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?

	$arCurrencyList = OBX_Currency::getListArray();
	wd($arCurrencyList, '$arCurrencyList');

	echo '<p>Тест создания валюты валюты</p>';
	OBX_Currency::add(array(
		"CURRENCY" => "TUG",
		"COURSE" => "1",
		"RATE" => "1",
		"IS_DEFAULT" => "Y"
	));
	wd(OBX_Currency::getListArray("TUG"), 'OBX_Currency::getList("TUG")');
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');
	echo 'попытка повторного добавления валюты(Обработка Duplicate Entry)<br />';
	OBX_Currency::add(array(
		"CURRENCY" => "TUG",
		"COURSE" => "1",
		"RATE" => "1",
		"IS_DEFAULT" => "Y"
	));
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');

	echo '<p>Тест обновления валюты</p>';
	OBX_Currency::update(array(
		"CURRENCY" => "TUG",
		"COURSE" => "2",
		"RATE" => "2",
		"IS_DEFAULT" => "Y"
	));
	wd(OBX_Currency::getListArray("TUG"), 'OBX_Currency::getList("TUG")');
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');
	echo 'Попытка обновления несуществующей валюты<br />';
	OBX_Currency::update(array(
		"CURRENCY" => "TUK",
		"COURSE" => "1",
		"RATE" => "1",
		"IS_DEFAULT" => "Y"
	));
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');
	
	echo '<p>Тест делает тугрики валютой по умолчанию</p>';
	OBX_Currency::setDefault("TUG");
	wd(OBX_Currency::getListArray(), 'OBX_Currency::getListArray()');

	echo '<p>Тест удаления валюты</p>';
	OBX_Currency::delete("TUG");
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');
	echo 'Попытка удаление несуществующей валюты';
	OBX_Currency::delete("TUK");
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');

?>
<?endif?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>