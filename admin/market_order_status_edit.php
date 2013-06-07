<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Market\CurrencyFormatDBS;
use OBX\Market\Order;
use OBX\Market\OrderDBS;
use OBX\Market\OrderStatusDBS;
use OBX\Market\OrderPropertyDBS;
use OBX\Market\OrderPropertyValuesDBS;
use OBX\Market\OrderPropertyEnumDBS;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
if(!CModule::IncludeModule('obx.market')) return;

// Доступ
//if (!$USER->CanDoOperation('edit_orders'))
//	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
//$isAdmin = $USER->CanDoOperation('edit_orders');
if (!$USER->IsAdmin()) {
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

IncludeModuleLangFile(__FILE__);

$ID = intval($ID); // идентификатор редактируемой записи

// Заголовок
if($ID>0) {
	$TITLE = GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_TAB_TITLE');

}
else {
	$TITLE = GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_TAB_TITLE_NEW');
}

$TabControl = new CAdminTabControl("tabControl", array(array(
	'DIV' => 'obx_order_prop_edit',
	'TAB' => GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_TAB'),
	'TITLE' => $TITLE,
	//"ICON" => "obx_market_order_prop_edit_tab_icon"
)));

if( !isset($_SESSION['_OBX_ORDER_STATUS_EDIT_ERRORS']) ) {
	$_SESSION['_OBX_ORDER_STATUS_EDIT_ERRORS'] = array();
}
$arErrors = &$_SESSION['_OBX_ORDER_STATUS_EDIT_ERRORS'];

$OrderStatusDBS = OrderStatusDBS::getInstance();
$arStatus = $OrderStatusDBS->getByID($ID);

if(empty($arStatus)) {
	$arStatus = array(
		'CODE' => '',
		'NAME' => '',
		'DESCRIPTION' => '',
		'PROPERTY_TYPE' => 'S',
		'SORT' => '100',
		'ACTIVE' => 'Y'
	);
}


if( $REQUEST_METHOD == "POST" // проверка метода вызова страницы
	&& ($save != "" || $apply != "") // проверка нажатия кнопок "Сохранить" и "Применить"
	//&& $POST_RIGHT == "W" // проверка наличия прав на запись для модуля
	&& check_bitrix_sessid() // проверка идентификатора сессии
) {

	$arStatus['CODE'] = $FIELDS['CODE'];
	$arStatus['NAME'] = $FIELDS['NAME'];
	$arStatus['DESCRIPTION'] = $FIELDS['DESCRIPTION'];
	$arStatus['COLOR'] = $FIELDS['COLOR'];
	$arStatus['SORT'] = $FIELDS['SORT'];
	$arStatus['ACTIVE'] = $FIELDS['ACTIVE'];
	if( !isset($arStatus['ID']) ) {
		if( ($newID = $OrderStatusDBS->add($arStatus)) ) {
			$arStatus = $OrderStatusDBS->getByID($newID);
		}
		else {
			$arErrors[] = GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_ERROR_1').': '.$OrderStatusDBS->popLastError();
		}
	}
	else {
		if( ($bUpdateSuccess = $OrderStatusDBS->update($arStatus)) ) {
			$arStatus = $OrderStatusDBS->getByID($arStatus['ID']);
		}
		else {
			$arErrors[] = GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_ERROR_2').': '.$OrderStatusDBS->popLastError();
		}
	}
	if ($apply != '') {
		// если была нажата кнопка 'Применить' - отправляем обратно на форму.
		LocalRedirect('/bitrix/admin/obx_market_order_status_edit.php?ID=' . $arStatus['ID'] . '&' . $TabControl->ActiveTabParam());
	}
	else {
		// если была нажата кнопка 'Сохранить' - отправляем к списку элементов.
		LocalRedirect('/bitrix/admin/obx_market_order_status.php');
	}

}


$APPLICATION->SetTitle($TITLE);
require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
if( count($arErrors)>0 ) {
	$strErrors = '';
	foreach($arErrors as &$strError) {
		$strErrors .= $strError."<br />\n";
	}
	$arErrors = array();
	CAdminMessage::ShowMessage($strErrors);
}
?>
<form method="POST" name="obx_order_prop_edit_form" Action="<?=$APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data" name="post_form">
	<?=bitrix_sessid_post();?>
	<input type="hidden" name="lang" value="<?=LANG?>">
	<?if ($ID > 0 && !$bCopy): ?>
	<input type="hidden" name="ID" value="<?=$ID?>">
	<? endif;

	// отобразим заголовки закладок
	$TabControl->Begin();
	$TabControl->BeginNextTab();
	?>

	<?if(false):?><table><?endif?>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_F_ACTIVE')?></td>
		<td width="70%"><input type="checkbox" name="FIELDS[ACTIVE]" value="<?=$arStatus['ACTIVE']?>"<?if($arStatus['ACTIVE']=='Y'):?> checked="checked"<?endif?> /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_F_CODE')?></td>
		<td><input type="text" name="FIELDS[CODE]" value="<?=$arStatus['CODE']?>" /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_F_NAME')?></td>
		<td><input type="text" name="FIELDS[NAME]" value="<?=$arStatus['NAME']?>" /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_F_DESCRIPTION')?></td>
		<td><textarea name="FIELDS[DESCRIPTION]" rows="3" cols="30"><?=$arStatus['DESCRIPTION']?></textarea></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_F_SORT')?></td>
		<td><input type="text" name="FIELDS[SORT]" value="<?=$arStatus['SORT']?>" /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_STATUS_EDIT_F_COLOR')?></td>
		<td><input type="text" name="FIELDS[COLOR]" value="<?=$arStatus['COLOR']?>" /></td>
	</tr>
	<?if(false):?></table><?endif?>

	<?
	$TabControl->Buttons(
		array(
			"disabled" => false,
			"back_url" => "obx_market_order_status.php",
		)
	);
	$TabControl->End();
	?>
</form>
<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
?>
