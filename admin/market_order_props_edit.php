<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

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
	$TITLE = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_TAB_TITLE');

}
else {
	$TITLE = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_TAB_TITLE_NEW');
}

$TabControl = new CAdminTabControl("tabControl", array(array(
	'DIV' => 'obx_order_prop_edit',
	'TAB' => GetMessage('OBX_MARKET_ORDER_PROP_EDIT_TAB'),
	'TITLE' => $TITLE,
	//"ICON" => "obx_market_order_prop_edit_tab_icon"
)));

if( !isset($_SESSION['_OBX_ORDER_PROP_EDIT_ERRORS']) ) {
	$_SESSION['_OBX_ORDER_PROP_EDIT_ERRORS'] = array();
}
$arErrors = &$_SESSION['_OBX_ORDER_PROP_EDIT_ERRORS'];

$OrderPropertyDBS = OBX_OrderPropertyDBS::getInstance();
$OrderPropertyEnumDBS = OBX_OrderPropertyEnumDBS::getInstance();
$arProperty = $OrderPropertyDBS->getByID($ID);

if(empty($arProperty)) {
	$arProperty = array(
		'CODE' => '',
		'NAME' => '',
		'DESCRIPTION' => '',
		'PROPERTY_TYPE' => 'S',
		'SORT' => '100',
		'ACTIVE' => 'Y',
		'ACCESS' => 'W',
	);
}


if( $REQUEST_METHOD == "POST" // проверка метода вызова страницы
	&& ($save != "" || $apply != "") // проверка нажатия кнопок "Сохранить" и "Применить"
	//&& $POST_RIGHT == "W" // проверка наличия прав на запись для модуля
	&& check_bitrix_sessid() // проверка идентификатора сессии
) {

	$arProperty['CODE'] = $FIELDS['CODE'];
	$arProperty['NAME'] = $FIELDS['NAME'];
	$arProperty['DESCRIPTION'] = $FIELDS['DESCRIPTION'];
	$arProperty['PROPERTY_TYPE'] = $FIELDS['PROPERTY_TYPE'];
	$arProperty['SORT'] = $FIELDS['SORT'];
	$arProperty['ACTIVE'] = $FIELDS['ACTIVE'];
	$arProperty['ACCESS'] = $FIELDS['ACCESS'];

	if( !isset($arProperty['ID']) ) {
		if( ($newID = $OrderPropertyDBS->add($arProperty)) ) {
			$arProperty = $OrderPropertyDBS->getByID($newID);
		}
		else {
			$arErrors[] = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_ERROR_1').': '.$OrderPropertyDBS->popLastError();
		}
	}
	else {
		if( ($bUpdateSuccess = $OrderPropertyDBS->update($arProperty)) ) {
			$arProperty = $OrderPropertyDBS->getByID($arProperty['ID']);
		}
		else {
			$arErrors[] = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_ERROR_2').': '.$OrderPropertyDBS->popLastError();
		}
	}
	if(count($arErrors)<1 && $arProperty['PROPERTY_TYPE'] == 'L') {
		if( count($LIST_VALUES)>0 ) {
			foreach($LIST_VALUES as &$arListValue) {
				if( $arListValue['ID']>0 ) {
					$arListValue['CODE'] = trim($arListValue['CODE']);
					$arListValue['VALUE'] = trim($arListValue['VALUE']);
					if( strlen($arListValue['CODE'])>0 && strlen($arListValue['VALUE'])>0 ) {
						$bEnumUpdSuccess = $OrderPropertyEnumDBS->update(array(
							'ID' => $arListValue['ID'],
							'CODE' => $arListValue['CODE'],
							'VALUE' => $arListValue['VALUE'],
							'SORT' => $arListValue['SORT'],
						));
						if(!$bEnumUpdSuccess) {
							$arErrors[] = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_ERROR_4').': '.$OrderPropertyEnumDBS->popLastError();
						}
					}
					if(
						(strlen($arListValue['CODE'])==0 && strlen($arListValue['VALUE'])==0)
						|| $arListValue['_DELETE'] == 'Y'
					) {
						$bEnumDeleteSuccess = $OrderPropertyEnumDBS->delete($arListValue['ID']);
						if( !$bEnumDeleteSuccess ) {
							$arErrors[] = $arErrors[] = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_ERROR_5').': '.$OrderPropertyEnumDBS->popLastError();
						}
					}
				}
				else {
					if( strlen($arListValue['CODE'])>0 && strlen($arListValue['VALUE'])>0 ) {
						$newEnumID = $OrderPropertyEnumDBS->add(array(
							'CODE' => $arListValue['CODE'],
							'VALUE' => $arListValue['VALUE'],
							'PROPERTY_ID' => $arProperty['ID'],
							'SORT' => $arListValue['SORT'],
						));
						if($newEnumID<=0) {
							$arErrors[] = GetMessage('OBX_MARKET_ORDER_PROP_EDIT_ERROR_3').': '.$OrderPropertyEnumDBS->popLastError();
						}
					}
				}
			}
		}
	}
	if ($apply != '') {
		// если была нажата кнопка 'Применить' - отправляем обратно на форму.
		LocalRedirect('/bitrix/admin/obx_market_order_props_edit.php?ID=' . $arProperty['ID'] . '&' . $TabControl->ActiveTabParam());
	}
	else {
		// если была нажата кнопка 'Сохранить' - отправляем к списку элементов.
		LocalRedirect('/bitrix/admin/obx_market_order_props.php');
	}

}

$arEnumList = array();
if($arProperty['PROPERTY_TYPE'] == 'L' && $arProperty['ID']>0) {
	$arEnumList = $OrderPropertyEnumDBS->getListArray(
		array('SORT' => 'ASC', 'ID' => 'ASC', 'CODE' => 'ASC'),
		array('PROPERTY_ID' => $arProperty['ID'])
	);
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
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_ACTIVE')?></td>
		<td width="70%"><input type="checkbox" name="FIELDS[ACTIVE]" value="<?=$arProperty['ACTIVE']?>"<?if($arProperty['ACTIVE']=='Y'):?> checked="checked"<?endif?> /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_CODE')?></td>
		<td>
			<input type="text" name="FIELDS[CODE]" value="<?=$arProperty['CODE']?>" />
		</td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_NAME')?></td>
		<td><input type="text" name="FIELDS[NAME]" value="<?=$arProperty['NAME']?>" /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_DESCRIPTION')?></td>
		<td><textarea name="FIELDS[DESCRIPTION]" rows="3" cols="30"><?=$arProperty['DESCRIPTION']?></textarea></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_SORT')?></td>
		<td><input type="text" name="FIELDS[SORT]" value="<?=$arProperty['SORT']?>" /></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_PROPERTY_TYPE')?></td>
		<td>
			<select name="FIELDS[PROPERTY_TYPE]"
					onchange="(function() {
						var obx_order_prop_enums_row = document.getElementById('obx_order_prop_enums_row');
						if(this.options[this.options.selectedIndex].value == 'L') {
							obx_order_prop_enums_row.style.display = '';
						}
						else {
							obx_order_prop_enums_row.style.display = 'none';
						}}).call(this)"
			>
				<option value="S"<?if($arProperty['PROPERTY_TYPE']=='S'):?> selected="selected"<?endif?>>[S] <?=GetMessage('OBX_MARKET_ORDER_PROP_TYPE_S')?></option>
				<option value="N"<?if($arProperty['PROPERTY_TYPE']=='N'):?> selected="selected"<?endif?>>[N] <?=GetMessage('OBX_MARKET_ORDER_PROP_TYPE_N')?></option>
				<option value="T"<?if($arProperty['PROPERTY_TYPE']=='T'):?> selected="selected"<?endif?>>[T] <?=GetMessage('OBX_MARKET_ORDER_PROP_TYPE_T')?></option>
				<option value="L"<?if($arProperty['PROPERTY_TYPE']=='L'):?> selected="selected"<?endif?>>[L] <?=GetMessage('OBX_MARKET_ORDER_PROP_TYPE_L')?></option>
				<option value="C"<?if($arProperty['PROPERTY_TYPE']=='C'):?> selected="selected"<?endif?>>[C] <?=GetMessage('OBX_MARKET_ORDER_PROP_TYPE_C')?></option>
			</select>
		</td>
	</tr>
	<tr id="obx_order_prop_enums_row"<?if($arProperty['PROPERTY_TYPE']!='L'):?> style="display: none;"<?endif?>>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_LIST_VALUES')?></td>
		<td>
			<table>
				<tr>
					<td>ID</td>
					<td width="30px"><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_CODE')?></td>
					<td></td>
					<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_VALUE')?></td>
					<td></td>
					<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_LST_VAL_SORT')?></td>
					<td><?if(count($arEnumList)>0):?><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_LST_VAL_DEL')?><?endif?></td>
				</tr>
				
				<?$iEnum=0;
				foreach($arEnumList as $arEnum):?>
				<tr>
					<td><?=$arEnum['ID']?><input type="hidden" name="LIST_VALUES[<?=$iEnum?>][ID]" value="<?=$arEnum['ID']?>" /></td>
					<td><input type="text" name="LIST_VALUES[<?=$iEnum?>][CODE]" size="10" value="<?=$arEnum['CODE']?>" /></td>
					<td></td>
					<td><input type="text" name="LIST_VALUES[<?=$iEnum?>][VALUE]" value="<?=$arEnum['VALUE']?>" /></td>
					<td></td>
					<td><input type="text" name="LIST_VALUES[<?=$iEnum?>][SORT]" size="3" value="<?=empty($arEnum['SORT'])?100:$arEnum['SORT']?>" /></td>
					<td><input type="checkbox" name="LIST_VALUES[<?=$iEnum?>][_DELETE]" value="Y" /></td>
				</tr>
				<?$iEnum++; endforeach;?>
				
				<?for($i=$iEnum; $i<($iEnum+5); $i++):?>
				<tr>
					<td></td>
					<td><input type="text" name="LIST_VALUES[<?=$i?>][CODE]" size="10" value="" /></td>
					<td><input type="hidden" name="LIST_VALUES[<?=$i?>][ID]" value="0" /></td>
					<td><input type="text" name="LIST_VALUES[<?=$i?>][VALUE]" value="" /></td>
					<td></td>
					<td><input type="text" name="LIST_VALUES[<?=$i?>][SORT]" size="3" value="100" /></td>
					<td></td>
				</tr>
				<?endfor?>
			</table>
		</td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_MARKET_ORDER_PROP_EDIT_F_ACCESS')?></td>
		<td>
			<select name="FIELDS[ACCESS]">
				<option value="W"<?if($arProperty['ACCESS']=='W'):?> selected="selected"<?endif?>>[W] <?=GetMessage('OBX_MARKET_ORDER_PROP_ACCESS_W')?></option>
				<option value="R"<?if($arProperty['ACCESS']=='R'):?> selected="selected"<?endif?>>[R] <?=GetMessage('OBX_MARKET_ORDER_PROP_ACCESS_R')?></option>
				<option value="S"<?if($arProperty['ACCESS']=='S'):?> selected="selected"<?endif?>>[S] <?=GetMessage('OBX_MARKET_ORDER_PROP_ACCESS_S')?></option>
			</select>
		</td>
	</tr>
	<?if(false):?></table><?endif?>

	<?
	$TabControl->Buttons(
		array(
			"disabled" => false,
			"back_url" => "obx_market_order_props.php",
		)
	);
	$TabControl->End();
	?>
</form>
<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
?>
