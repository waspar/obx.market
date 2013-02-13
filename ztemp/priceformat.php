<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ) {

	function format1($priceValue, $arFormat) {
		$priceValue = floatval($priceValue);
		$valueInt = intval($priceValue);
		$valueDec = round($priceValue*pow(10, $arFormat["DEC_PRECISION"]) - $valueInt*pow(10, $arFormat["DEC_PRECISION"]), 0, PHP_ROUND_HALF_DOWN);
		if(!$valueInt) {
			$valueInt = '0';
		}
		$cent = '%';
		if(!$valueDec) {
			//$valueDec = '00';
			$valueDec = '';
			if( strpos($arFormat["FORMAT"], ',%')!==false ) {
				$valueDec = '';
				$cent = ',%';
			}
			elseif( strpos($arFormat["FORMAT"], '.%')!==false ) {
				$valueDec = '';
				$cent = '.%';
			}
		}
		return str_replace(array("#", $cent), array($valueInt, $valueDec), $arFormat["FORMAT"]);

	}

	function format2($priceValue, $arFormat) {
		$priceValue = floatval($priceValue);
		$valueInt = intval($priceValue);
		$valueDec = intval($priceValue*pow(10, $arFormat['DEC_PRECISION']) - $valueInt*pow(10, $arFormat['DEC_PRECISION']));
		if(!$valueInt) {
			$valueInt = '0';
		}
		return sprintf($arFormat['FORMAT'], $valueInt, $valueDec);
	}

	function format3($priceValue, $arFormat) {
		return money_format($arFormat['FORMAT'], $priceValue);
	}

	$priceValue = 5432.908;
	$iCount = 10000;

	// performance

	//////////////////////////////
	$arFormat = array(
		'DEC_PRECISION' => 4,
		'FORMAT' => '# рублей % коп.'
	);
	$start = microtime(true);
	echo "Цена: ".format1($priceValue, $arFormat)."<br />\n";
	for($i=0; $i<$iCount; $i++) {
		format1($priceValue, $arFormat);
	}
	$stop = microtime(true);
	echo 'Время работы 1 алгоритма в '.$iCount.' итерациях: '.($stop - $start)."<br /><br />\n";
	//////////////////////////////

	//////////////////////////////
	$start = microtime(true);
	$arFormat = array(
		'DEC_PRECISION' => 4,
		'FORMAT' => '%1$u рублей %2$u коп.'
	);
	echo "Цена: ".format2($priceValue, $arFormat)."<br />\n";
	for($i=0; $i<$iCount; $i++) {
		format2($priceValue, $arFormat);
	}
	$stop = microtime(true);
	echo 'Время работы 2 алгоритма в '.$iCount.' итерациях: '.($stop - $start)."<br /><br />\n";
	//////////////////////////////

	//////////////////////////////
	$start = microtime(true);
	$arFormat = array(
		'DEC_PRECISION' => 4,
		'FORMAT' => '%.2n'
	);
	//echo "Цена: ".format3($priceValue, $arFormat)."<br />\n";
	echo "Цена: ".money_format($arFormat['FORMAT'], $priceValue)."<br />\n";
	for($i=0; $i<$iCount; $i++) {
		//format3($priceValue, $arFormat);
		money_format($arFormat['FORMAT'], $priceValue);
	}
	$stop = microtime(true);
	echo 'Время работы 3 алгоритма в '.$iCount.' итерациях: '.($stop - $start)."<br /><br />\n";
	//////////////////////////////


}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>