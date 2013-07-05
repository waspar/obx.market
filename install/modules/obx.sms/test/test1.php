<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("__hide_footer", "Y");

if (!CModule::IncludeModule("obx.sms"))
	die();

$object = \OBX\Sms\SmsSender::factory("SMSKONTAKT");
wd($object, 'providerObject');
$result = $object->send("79135591313","test message");
wd($object->getLastError());
//$result = $object->send("79135295396","test message");
//wd($object->requestBalance(),"Balance");
wd($result,"result");

//wd(OBX_SmsSender::popLastError(), 'OBX_SmsSender::popLastError()');

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>