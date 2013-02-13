<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):
class OBX_Test_CIBlockPropertyPrice
{
	static public function main()
	{
//		OBX_CIBlockPropertyPrice::registerModuleDependencies(); die();
		
		echo '<p>Создание тестовых инфоблоков</p>';
		$arTestIBlockID = array();
		for($i=0; $i < 4; $i++)
		{
			$TestIBlock = new CIBlock;
			$arIBlockFields = array(
				"CODE" => "obx_test_price_iblock_$i",
				"NAME" => "Тестовый инфоблок #$i",
				"ACTIVE" => "Y",
				"SITE_ID" => SITE_ID,
				"IBLOCK_TYPE_ID" => OBX_SERVICE_IBTYPE
			);
			wd($arIBlockFields, '$arIBlockFields');
			$rsExists = CIBlock::GetList(array(), array("CODE" => $arIBlockFields["CODE"]));
			if( $arExists = $rsExists->Fetch() ) {
				echo "Инфоблок уже существует<br />\n";
				$testIBlockID = $arExists["ID"];
			}
			else {
				$testIBlockID = $TestIBlock->Add($arIBlockFields);
				if(!$testIBlockID) {
					echo "Не удалось создать тестовый инфоблок<br />\n";
					return;
				}
			}
			$arTestIBlockID[] = $testIBlockID;
		}
		wd($arTestIBlockID, '$arTestIBlockID');

		echo '<p>Делаем тестовые инфоблоки торговыми</p>';
		foreach($arTestIBlockID as $testIBlockID) {
			OBX_ECommerceIBlock::add(array("IBLOCK_ID" => $testIBlockID));
		}
		wd(OBX_ECommerceIBlock::getErrors(), 'OBX_ECommerceIBlock::getErrors()');
		OBX_ECommerceIBlock::clearErrors();
		
		echo '<p>Выборка связок</p>';
		$arCIBEPrice = OBX_CIBlockPropertyPrice::getListArray(null, array("PRICE_ID" => "2"));
		wd($arCIBEPrice, '$arCIBEPrice');


		echo 'Добавляем новые цены-свойства в ИБ-ки<br />';
		$arNewLinkID = array();
		foreach($arTestIBlockID as $testIBlockID) {
			$arNewLinkID[] = OBX_CIBlockPropertyPrice::addIBlockPriceProperty(
					array("PRICE_CODE"=>"BASE", "IBLOCK_ID" => $testIBlockID)
				);
			wd('OBX_CIBlockPropertyPrice::popLastError(): '.OBX_CIBlockPropertyPrice::popLastError());;
			$arNewLinkID[] = OBX_CIBlockPropertyPrice::addIBlockPriceProperty(
					array("PRICE_CODE"=>"PRICE", "IBLOCK_ID" => $testIBlockID)
				);
			wd('OBX_CIBlockPropertyPrice::popLastError(): '.OBX_CIBlockPropertyPrice::popLastError());;
		}
		$arCIBEPrice = OBX_CIBlockPropertyPrice::getListArray();
		wd($arCIBEPrice, '$arCIBEPrice');

//		echo '<p>Удаляем привязки цен к товарам</p>';
//		foreach($arNewLinkID as $linkID) {
//			echo 'Удаление ID='.$linkID.': '
//				.((OBX_CIBlockPropertyPrice::delete($linkID, false))?'OK':'FAILED: '.OBX_CIBlockPropertyPrice::popLastError())."<br />\n";
//		}
//
//		echo '<p>Удяляем тестовые инфоблоки</p>';
//		foreach($arTestIBlockID as $testIBlockID) {
//			echo 'Удаление инфоблока ID='.$testIBlockID.': '.(CIBlock::Delete($testIBlockID)?"OK":"FAILED")."<br />\n";
//		}
	}
}
OBX_Test_CIBlockPropertyPrice::main();
endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>