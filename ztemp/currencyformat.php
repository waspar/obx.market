<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");?>
<?if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<?
	echo '<p>Создание тестовой валюты</p>';
	OBX_Currency::add(array(
		"CURRENCY" => "TUG",
		"COURSE" => 1,
		"RATE" => 1
	));
	wd(OBX_Currency::popLastError(), 'OBX_Currency::popLastError()');
	wd(OBX_Currency::getListArray("TUG"), 'OBX_Currency::getListArray("TUG")');

	echo '<p>Тест создания формата цены для рублей</p>';
	echo 'Попробуем пропустить важные поля. Например Имя и/или Валюту';
	OBX_CurrencyFormat::add(array(
		//"CURRENCY" => "TUG",
		//"NAME" => "Тугрики",
		"FORMAT" => "# туг. % тугроцент.",
		"THOUSANDS_SEP" => "'",
	));
	wd(OBX_CurrencyFormat::getErrors(), 'OBX_CurrencyFormat::getErrors()');
	OBX_CurrencyFormat::clearErrors();
	echo 'Теперь реально добавляем значения<br />';
	echo 'для русского языка';
	$newID = OBX_CurrencyFormat::add(array(
		"CURRENCY" => "TUG",
		"NAME" => "Тугрики",
		"FORMAT" => "# туг. % тугроцент.",
	));
	wd(OBX_CurrencyFormat::getByID($newID), 'OBX_CurrencyFormat::getByID($newID)');
	wd(OBX_CurrencyFormat::popLastError(), 'OBX_CurrencyFormat::popLastError()');
	echo 'для английского языка';
	OBX_CurrencyFormat::add(array(
		"CURRENCY" => "TUG",
		"NAME" => "Tugrik",
		"LANGUAGE_ID" => 'en',
		"FORMAT" => "#.% Tugrik",
		"THOUSANDS_SEP" => "'",
	));
	wd(OBX_CurrencyFormat::popLastError(), 'OBX_CurrencyFormat::popLastError()');
	wd(OBX_CurrencyFormat::getListArray(), 'OBX_CurrencyFormat::getListArray()');
	echo '<p>Тест на обработку Duplicate Entry</p>';
	OBX_CurrencyFormat::add(array(
		"CURRENCY" => "TUG",
		"LANGUAGE" => "en",
		"NAME" => "asdf"
	));
	wd(OBX_CurrencyFormat::popLastError(), 'OBX_CurrencyFormat::popLastError()');

	echo '<p>Обновляем вновь созданные языковые форматы</p>';
	echo 'для русского языка<br />';
	$arRuF = OBX_CurrencyFormat::getListArray(null, array("CURRENCY" => "TUG", "LANGUAGE_ID" => "ru"));
	$arRuF = $arRuF[0];
	$arRuF["NAME"] = "Тугрики изм";
	$arRuF["FORMAT"] = "# туг. % тугроцент. изм";
		// ОПА
		$arRuF["LANGUAGE_ID"] = "en";
	$arRuF["THOUSANDS_SEP"] = '`';
	$arRuF["DEC_PRECISION"] = 3;
	OBX_CurrencyFormat::update($arRuF);
	wd(OBX_CurrencyFormat::popLastError(), 'OBX_CurrencyFormat::popLastError()');

	echo 'для английского языка<br />';
	$arEnF = OBX_CurrencyFormat::getListArray(null, array("CURRENCY" => "TUG", "LANGUAGE_ID" => "en"));
	$arEnF = $arEnF[0];
	$arEnF["NAME"] = "Tugrik changed";
	$arEnF["THOUSANDS_SEP"] = '`';
	$arEnF["DEC_PRECISION"] = 3;
	OBX_CurrencyFormat::update($arEnF);
	wd(OBX_CurrencyFormat::popLastError(), 'OBX_CurrencyFormat::popLastError()');
	wd(OBX_CurrencyFormat::getListArray(), 'OBX_CurrencyFormat::getListArray()');

	echo '<p>Удаляем тестовые форматы валют</p>';
	//OBX_CurrencyFormat::delete($arRuF["ID"]);
	//OBX_CurrencyFormat::delete($arEnF["ID"]);
	OBX_CurrencyFormat::deleteByFilter(array("CURRENCY" => "TUG"));
	echo 'Удяляем чего-нить несуществующее<br />';
	OBX_CurrencyFormat::delete(9876123);
	echo 'Удяляем тестовуб валюту<br />';
	OBX_Currency::delete("TUG");
	wd(OBX_CurrencyFormat::getErrors(), 'OBX_CurrencyFormat::getErrors()');
	wd(OBX_Currency::getErrors(), 'OBX_Currency::getErrors()');

?>
<?endif?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>