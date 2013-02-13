<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetPageProperty("__hide_footer", "Y");
if( CModule::IncludeModule("obx.market") && $USER->IsAdmin() ):?>
<h2>Добавляем заказ:</h2>
<?
	$arFields = array(
		"CURRENCY" => "RUB"
	);
	$arOrder = OBX_OrdersDBS::getInstance();

	//$newOrder = OBX_OrdersList::add($arFields);

	//wd($newOrder,"Action");
	//wd(OBX_OrdersList::popLastError(),"errors");
	?>
<h2>Удаляем заказ:</h2>
<?
	//wd(OBX_OrdersList::delete($newOrder),"Action");
	//wd(OBX_OrdersList::popLastError(),"errors");
	?>
<h2>Удаляем по фильтру</h2>
<?
	$arFields = array(
		"CURRENCY" => "RUB"
	);
	//$newOrder = OBX_OrdersList::add($arFields);
	//wd(OBX_OrdersList::deleteByFilter($arFields),"Action");
	//wd(OBX_OrdersList::popLastError(),"errors");
	?>
<h2>Получаем лист</h2>
<?
	$newOrdersList = array();
	//$newOrdersList[] = OBX_OrdersList::add($arFields);
	//$newOrdersList[] = OBX_OrdersList::add($arFields);
	//$newOrdersList[] = OBX_OrdersList::add($arFields);

	$arOrders = array();
	$rsOrders = OBX_OrdersList::getList();
	while ($arOrder = $rsOrders->Fetch()){
		$arOrders[] = $arOrder;
	}
	wd($arOrders,"Action");
	wd(OBX_OrdersList::popLastError(),"errors");
	//wd(OBX_OrdersList::deleteByFilter($arFields),"Delete");
	?>
<h2>Получаем по ID</h2>
	<?
	//$newOrder = OBX_OrdersList::add($arFields);
	//wd(OBX_OrdersList::getByID($newOrder),"Action");
	//wd(OBX_OrdersList::popLastError(),"errors");
	//wd(OBX_OrdersList::deleteByFilter($arFields),"Delete");
		?>
<h2>Пуш из корзины</h2>
	<?
	$Order = new OBX_Order();
	$newOrder = $Order->add(array());
	wd($Order->getOrderFields(),'test');
	wd($Order->setProductListFromBasket());
	?>
<?endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
