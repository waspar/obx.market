<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?
	echo '<p>Добавляем базовую цену в рублях</p>';
	OBX_Price::add(array(
		"CODE" => "BASE",
		"NAME" => "Базовая цена",
		"CURRENCY" => "RUB"
	));
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	wd(OBX_Price::getListArray(), 'OBX_Price::getListArray()');
	
	echo '<p>Добавляем розничную цену в рублях</p>';
	OBX_Price::add(array(
		"CODE" => "PRICE",
		"NAME" => "Розничная цена",
		"CURRENCY" => "RUB"
	));
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	wd(OBX_Price::getListArray(), 'OBX_Price::getListArray()');
	
	echo '<p>Добавляем базовую цену в долларах</p>';
	OBX_Price::add(array(
		"CODE" => "BASE_USD",
		"NAME" => "Базовая - доллары",
		"CURRENCY" => "USD"
	));
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	wd(OBX_Price::getListArray(), 'OBX_Price::getListArray()');
	
	echo '<p>Добавляем розничную цену в долларах</p>';
	OBX_Price::add(array(
		"CODE" => "PRICE_USD",
		"NAME" => "Розничная - доллары",
		"CURRENCY" => "USD"
	));
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	wd(OBX_Price::getListArray(), 'OBX_Price::getListArray()');
	
	echo '<p>Добавляем тестовую цену для тестирования обновления</p>';
	OBX_Price::add(array(
		"CODE" => "_TEST_",
		"NAME" => "Тестовая цена в рублях",
		"CURRENCY" => "RUB"
	));
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	wd(OBX_Price::getByCode("_TEST_"), 'OBX_Price::getByCode("_TEST_")');
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	OBX_Price::update(array(
		"CODE" => "_TEST_",
		"NAME" => "Тестовая цена теперь в долларах",
		"CURRENCY_CODE" => "USD"
	));
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	$arTestPrice = OBX_Price::getByCode("_TEST_");
	wd($arTestPrice, '$arTestPrice');

	echo '<br />Удаляем тестовую цену<br />';
	OBX_Price::delete($arTestPrice["ID"]);
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	echo 'Попытка удаления несуществующей цены<br />';
	OBX_Price::delete(234523);
	wd('OBX_Price::popLastError(): '.OBX_Price::popLastError());
	
	echo '<p>Выборка</p>';
	$arSort = array(
		"SORT" => "ASC",
		"CODE" => "ASC",
		"ID" => "ASC",
		"CURRENCY_LANG_ID" => "ASC"
	);
	$arFilter = array(
		//"CODE" => "BASE"
	);
	$arSelect = array(
		//"ID", "NAME", "CURRENCY_CODE", "CURRENCY_NAME"
	);
	wd(OBX_Price::getListArray($arSort, $arFilter, $arSelect), 'OBX_Price::getListArray($arSort, $arFilter, $arSelect)');

//	echo '<p>Форматирование цен</p>';
//	wd('OBX_Price::formatPrice(3.123, "BASE"): '.OBX_Price::formatPrice(3.123, "BASE"));
//	wd('OBX_Price::formatPrice(3.123, "PRICE"): '.OBX_Price::formatPrice(3.123, "PRICE"));
//	wd('OBX_Price::formatPrice(3.123, "BASE_USD"): '.OBX_Price::formatPrice(3.123, "BASE_USD"));
//	wd('OBX_Price::formatPrice(3.123, "PRICE_USD"): '.OBX_Price::formatPrice(3.123, "PRICE_USD"));
	
?>
<?endif?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>