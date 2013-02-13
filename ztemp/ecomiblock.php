<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):
class OBX_Test_CECommerceIBlockPrice
{
	static public function main()
	{
		echo '<p>Создаем торговые инфоблоки</p>';
		OBX_ECommerceIBlock::add(array(
			"IBLOCK_ID" => 1
		));
		OBX_ECommerceIBlock::add(array(
			"IBLOCK_ID" => 2
		));
		OBX_ECommerceIBlock::add(array(
			"IBLOCK_ID" => 3,
			"PRICE_VERSION" => 3
		));
		OBX_ECommerceIBlock::add(array(
			"IBLOCK_ID" => 4
		));
		wd(OBX_ECommerceIBlock::getListArray(), 'OBX_ECommerceIBlock::getListArray()');
		wd(OBX_ECommerceIBlock::getErrors(), 'OBX_ECommerceIBlock::getErrors()');
		OBX_ECommerceIBlock::clearErrors();
//		echo '<p>Удаляем торговые инфоблоки</p>';
//		OBX_ECommerceIBlock::delete(1);
//		OBX_ECommerceIBlock::delete(2);
//		OBX_ECommerceIBlock::delete(3);
//		OBX_ECommerceIBlock::delete(4);
		wd(OBX_ECommerceIBlock::getListArray(), 'OBX_ECommerceIBlock::getListArray()');
		wd(OBX_ECommerceIBlock::getErrors(), 'OBX_ECommerceIBlock::getErrors()');
		OBX_ECommerceIBlock::clearErrors();

		echo '<p>Попытка создать что-нить левое</p>';
		OBX_ECommerceIBlock::add(array("IBLOCK_ID" => 12333));
		wd(OBX_ECommerceIBlock::popLastError(), 'OBX_ECommerceIBlock::popLastError()');

		wd(OBX_ECommerceIBlock::getFullList(), 'OBX_ECommerceIBlock::getFullList()');
	}
}
OBX_Test_CECommerceIBlockPrice::main();
endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>