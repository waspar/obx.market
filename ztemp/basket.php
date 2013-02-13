<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ) {
	$Basket = OBX_Basket::getInstance();

	$Basket->clearBasket();
	$Basket->addItem('1', 3);
	$Basket->addItem('2', 2);
	$Basket->addItem('3', 5);
	$Basket->addItem('4', 2);
	$Basket->addItem('5', 7);
	wd($Basket->getItemsList(), '$Basket->getItemsList()');
	wd($Basket->getProductsList(), '$Basket->getProductsList()');
	wd('$Basket->getProductsCount(): '.$Basket->getProductsCount());
	wd('$Basket->getItemsCount(): '.$Basket->getItemsCount());
	wd('$Basket->getProductItemsCount(1) '.$Basket->getProductItemsCount(1));
	wd('$Basket->getProductItemsCount(2) '.$Basket->getProductItemsCount(2));
	wd('$Basket->getProductItemsCount(3) '.$Basket->getProductItemsCount(3));
	wd('$Basket->getProductItemsCount(4) '.$Basket->getProductItemsCount(4));
	wd('$Basket->getProductItemsCount(5) '.$Basket->getProductItemsCount(5));
	wd('$Basket->getProductPrice(1): '.$Basket->getProductPrice(1));
	wd('$Basket->getProductPrice(2): '.$Basket->getProductPrice(2));
	wd('$Basket->getProductPrice(3): '.$Basket->getProductPrice(3));
	wd('$Basket->getProductPrice(4): '.$Basket->getProductPrice(4));
	wd('$Basket->getProductPrice(5): '.$Basket->getProductPrice(5));
	wd('$Basket->getProductCost(1): '.$Basket->getProductCost(1));
	wd('$Basket->getProductCost(2): '.$Basket->getProductCost(2));
	wd('$Basket->getProductCost(3): '.$Basket->getProductCost(3));
	wd('$Basket->getProductCost(4): '.$Basket->getProductCost(4));
	wd('$Basket->getProductCost(5): '.$Basket->getProductCost(5));
	wd('$Basket->getBasketCost(): '.$Basket->getBasketCost());

	wd($Basket->getErrors(), '$Basket->getErrors()');
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>