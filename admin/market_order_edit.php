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

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');
if (!CModule::IncludeModule('obx.market')) return;

// Доступ
//if (!$USER->CanDoOperation('edit_orders'))
//	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
//$isAdmin = $USER->CanDoOperation('edit_orders');
if (!$USER->IsAdmin()) {
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

IncludeModuleLangFile(__FILE__);

/**
 * @var CDatabase $DB
 */

$ID = intval($ID); // идентификатор редактируемой записи

$APPLICATION->AddHeadScript("/bitrix/js/obx.market/jquery-1.8.2.min.js");

// Заголовок
if ($ID > 0) {
	$TITLE = GetMessage('OBX_MARKET_ORDER_EDIT_TAB_TITLE', array('#ID#' => intval($ID)));

} else {
	$TITLE = GetMessage('OBX_MARKET_ORDER_EDIT_TAB_TITLE_NEW');
}

$TabControl = new CAdminTabControl("tabControl", array(
	array(
		'DIV' => 'obx_order_edit',
		'TAB' => GetMessage('OBX_MARKET_ORDER_EDIT_TAB'),
		'TITLE' => $TITLE,
		//"ICON" => "obx_market_order_edit_tab_icon"
	),
	array(
		'DIV' => 'obx_order_items_edit',
		'TAB' => GetMessage('OBX_MARKET_ORDER_ITEMS_EDIT_TAB'),
		'TITLE' => $TITLE,
		//"ICON" => "obx_market_order_edit_tab_icon"
	),
//	array(
//		'DIV' => 'obx_order_comments_edit',
//		'TAB' => GetMessage('OBX_MARKET_ORDER_COMMENTS_EDIT_TAB'),
//		'TITLE' => $TITLE,
//		//"ICON" => "obx_market_order_edit_tab_icon"
//	),
));

if (!isset($_SESSION['_OBX_ORDER_EDIT_ERRORS'])) {
	$_SESSION['_OBX_ORDER_EDIT_ERRORS'] = array();
}
$arErrors = & $_SESSION['_OBX_ORDER_EDIT_ERRORS'];

$Order = OBX_Order::getOrder($ID, $arError);

if (!empty($arError)) {
	$arErrors[] = $arError;
}

$arStatusList = OBX_OrderStatusDBS::getInstance()->getListArray();
$arCurrencyFormatList = OBX_CurrencyFormatDBS::getInstance()->getListGroupedByLang();
$currencyDefault = OBX_CurrencyDBS::getInstance()->getDefault();

$OrdersDBS = OBX_OrdersDBS::getInstance();
$OrderPropertyEnumDBS = OBX_OrderPropertyEnumDBS::getInstance();

$arOrder = array();
$arOrderStatus = array();
$arOrderPropertyValues = array();
$arPriceTypes = OBX_PriceDBS::getInstance()->getListArray();

if ($REQUEST_METHOD == "POST" // проверка метода вызова страницы
	&& ($save != "" || $apply != "") // проверка нажатия кнопок "Сохранить" и "Применить"
	//&& $POST_RIGHT == "W" // проверка наличия прав на запись для модуля
	&& check_bitrix_sessid() // проверка идентификатора сессии
) {
	$DB->StartTransaction();
	$arItems = $_REQUEST['TABLE'];
	$arProps = $_REQUEST['PROPERTIES'];

	if ($ID > 0) {
		$bSuccess = false;
		if ($Order->setFields($_REQUEST['FIELDS'])) {
			$bSuccess = true;

			if (!empty($arItems)) {
				$arFilteredItems = array();

				foreach($arItems as $arItem){
					if ($arItem['TO_DELETE'] !="Y"){
						$arFilteredItems[] = $arItem;
					}
				}

				$bSuccess = $Order->setItems($arFilteredItems,true);
			}
			if (!empty($arProps)) {
				$bSuccess = $Order->setProperties($arProps);
			}
		}

	} else {

		$newID = $OrdersDBS->add($_REQUEST['FIELDS']);
		$bSuccess = ($newID > 0) ? true : false;
		if ($bSuccess) {
			$ID = $newID;
			$Order = OBX_Order::getOrder($ID, $arError);
			if (!empty($arItems)) {
				$arFilteredItems = array();

				foreach($arItems as $arItem){
					if ($arItem['TO_DELETE'] !="Y"){
						$arFilteredItems[] = $arItem;
					}
				}

				$bSuccess = $Order->setItems($arFilteredItems,true);
			}
			if (!empty($arProps)) {
				$bSuccess = $Order->setProperties($arProps);
			}
		}else{
			//выкинуть ошибку
		}

	}

	if ($bSuccess) {
		$arOrder = $Order->getByID($ID);
	}

	if ($bSuccess) {
		$DB->Commit();
	} else {
		$DB->Rollback();
	}

	if ($apply != '') {
		// если была нажата кнопка 'Применить' - отправляем обратно на форму.
		LocalRedirect('/bitrix/admin/obx_market_order_edit.php?ID=' . $ID . '&' . $TabControl->ActiveTabParam());
	} else {
		// если была нажата кнопка 'Сохранить' - отправляем к списку элементов.
		LocalRedirect('/bitrix/admin/obx_market_orders.php');
	}
}

if ($ID > 0) {
	$arOrder = $Order->getFields();
	$arOrderItems = $Order->getItems();
}
if (array_key_exists('ID', $arOrder) && $arOrder['ID'] > 0) {
	$ID = $arOrder['ID']; // на случай если это новый заказ
	$arOrderStatus = $Order->getStatus();
	$arOrderPropertyValues = $Order->getProperties();

	foreach ($arOrderPropertyValues as &$arPropertyValue) {
		if ($arPropertyValue['PROPERTY_TYPE'] == 'L') {
			$arEnums = $OrderPropertyEnumDBS->getListArray(
				null,
				array(
					'PROPERTY_ID' => $arPropertyValue['PROPERTY_ID'],
				),
				null,
				null,
				array(
					'ID', 'CODE', 'NAME', 'VALUE'
				)
			);
			$arPropertyValue['PROPERTY_ENUM_VALUES'] = $arEnums;
		}
	}

} else {
	$arOrder = array(
		'STATUS_ID' => 1,
		'CURRENCY' => $currencyDefault,
	);
	$arOrderStatus = each($arStatusList);
	$arOrderStatus = $arOrderStatus['value'];
	$arOrderPropertyValues = array();
	$OrderPropertyDBS = OBX_OrderPropertyDBS::getInstance();
	$arPropertyList = $OrderPropertyDBS->getListArray();
	foreach ($arPropertyList as &$arProperty) {
		$arOrderPropertyValue = array(
			'PROPERTY_ID' => $arProperty['ID'],
			'NAME' => $arProperty['NAME'],
			'PROPERTY_TYPE' => $arProperty['PROPERTY_TYPE'],
			'VALUE' => null,
			'VALUE_S' => null,
			'VALUE_N' => null,
			'VALUE_T' => null,
			'VALUE_L' => null,
			'VALUE_C' => null,
		);
		if ($arOrderPropertyValue['PROPERTY_TYPE'] == 'L') {
			$arEnums = $OrderPropertyEnumDBS->getListArray(
				null,
				array(
					'PROPERTY_ID' => $arOrderPropertyValue['PROPERTY_ID'],
				),
				null,
				null,
				array(
					'ID', 'CODE', 'NAME', 'VALUE'
				)
			);
			$arOrderPropertyValue['PROPERTY_ENUM_VALUES'] = $arEnums;
		}
		$arOrderPropertyValues[] = $arOrderPropertyValue;
	}
}


$APPLICATION->SetTitle($TITLE);
require_once ($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');
if (count($arErrors) > 0) {
	$strErrors = '';
	foreach ($arErrors as &$strError) {
		$strErrors .= $strError . "<br />\n";
	}
	$arErrors = array();
	CAdminMessage::ShowMessage($strErrors);
}
?>
<form method="post" name="obx_order_edit_form" action="<?echo $APPLICATION->GetCurPage()?>"
	  enctype="multipart/form-data">
<?=bitrix_sessid_post();?>
<input type="hidden" name="lang" value="<?=LANG?>">
<?if ($ID > 0 && !$bCopy): ?>
<input type="hidden" name="ID" value="<?=$ID?>">
	<? endif;

// отобразим заголовки закладок
$TabControl->Begin();
$TabControl->BeginNextTab();
?>

<tr>
	<td width="30%"><?=GetMessage('OBX_MARKET_ORDER_EDIT_DATE_CREATED')?></td>
	<td><?=$arOrder['DATE_CREATED']?></td>
</tr>
<tr>
	<td><?=GetMessage('OBX_MARKET_ORDER_EDIT_TIMESTAMP_X')?></td>
	<td><?=$arOrder['TIMESTAMP_X']?></td>
</tr>
<tr>
	<td><?=GetMessage('OBX_MARKET_ORDER_EDIT_STATUS')?></td>
	<td>
		<select name="FIELDS[STATUS_ID]">
			<?foreach ($arStatusList as &$arStatus):
			$sSelected = '';
			if ($arStatus['ID'] == $arOrder['STATUS_ID']) {
				$sSelected = ' selected="selected"';
			}
			?>
			<option<?=$sSelected?> value="<?=$arStatus['ID']?>">[<?=$arStatus['ID']?>
				] <?=$arStatus['NAME']?></option>
			<? endforeach?>
		</select>
	</td>
</tr>
<tr>
	<td><?=GetMessage('OBX_MARKET_ORDER_EDIT_USER_ID')?></td>
	<td>
		<input type="text" name="FIELDS[USER_ID]" id="ORDER_FIELD_USER_ID" value="<?=$arOrder['USER_ID']?>"
			   size="3"
			   maxlength="" class="typeinput">
		<iframe style="width:0px; height:0px; border: 0px; display: none;" src="javascript:void(0)"
				name="hiddenframeORDER_FIELD_USER_ID" id="hiddenframeORDER_FIELD_USER_ID"></iframe>
		<input class="tablebodybutton" type="button" name="FindUserORDER_FIELD_USER_ID"
			   id="FindUserORDER_FIELD_USER_ID"
			   onclick="window.open('/bitrix/admin/user_search.php?lang=ru&amp;FN=obx_order_edit_form&amp;FC=ORDER_FIELD_USER_ID', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));"
			   value="...">
			<span id="div_ORDER_FIELD_USER_ID">[<a title="<?=GetMessage('_USER_PROFILE')?>"
												   href="/bitrix/admin/user_edit.php?ID=<?=$arOrder['USER_ID']?>&amp;lang=ru"><?=$arOrder['USER_ID']?></a>]</span>
		<script>
			var tvORDER_FIELD_USER_ID = '';

			function ChORDER_FIELD_USER_ID() {
				var DV_ORDER_FIELD_USER_ID;
				DV_ORDER_FIELD_USER_ID = document.getElementById("div_ORDER_FIELD_USER_ID");
				if (
						document.obx_order_edit_form
								&& document.obx_order_edit_form['ORDER_FIELD_USER_ID']
								&& typeof tvORDER_FIELD_USER_ID != 'undefined'
								&& tvORDER_FIELD_USER_ID != document.obx_order_edit_form['ORDER_FIELD_USER_ID'].value
						) {
					tvORDER_FIELD_USER_ID = document.obx_order_edit_form['ORDER_FIELD_USER_ID'].value;
					if (tvORDER_FIELD_USER_ID != '') {
						DV_ORDER_FIELD_USER_ID.innerHTML = '<i><?=GetMessage('WAIT___')?></i>';
						document.getElementById("hiddenframeORDER_FIELD_USER_ID").src = '/bitrix/admin/get_user.php?ID=' + tvORDER_FIELD_USER_ID + '&strName=ORDER_FIELD_USER_ID&lang=ru&admin_section=Y';
					}
					else {
						DV_ORDER_FIELD_USER_ID.innerHTML = '';
					}
				}
				else if (
						DV_ORDER_FIELD_USER_ID
								&& DV_ORDER_FIELD_USER_ID.innerHTML.length > 0
								&& document.obx_order_edit_form
								&& document.obx_order_edit_form['ORDER_FIELD_USER_ID']
								&& document.obx_order_edit_form['ORDER_FIELD_USER_ID'].value == ''
						) {
					document.getElementById('div_ORDER_FIELD_USER_ID').innerHTML = '';
				}
				setTimeout(function () {
					ChORDER_FIELD_USER_ID()
				}, 1000);
			}
			ChORDER_FIELD_USER_ID();
			//-->
		</script>
	</td>
</tr>
<tr>
	<td><?=GetMessage('OBX_MARKET_ORDER_EDIT_CURRENCY')?></td>
	<td>
		<input type="hidden" name="FIELDS[CURRENCY]" value="<?=$currencyDefault?>"/>
		<select name="FIELDS[CURRENCY]" disabled="disabled">
			<?foreach ($arCurrencyFormatList as $currency => &$arCurrencyFormat): ?>
			<option<?if ($currency == $arOrder['CURRENCY']): ?> selected="selected"<? endif?>
																value="<?=$currency?>"><?=$arCurrencyFormat['LANG'][LANGUAGE_ID]['NAME']?></option>
			<? endforeach;?>
		</select>

	</td>
</tr>
<tr class="heading">
	<td colspan="2">
		<?=GetMessage('OBX_MARKET_ORDER_PROP_VALUES_EDIT_TAB')?>
	</td>
</tr>
<?foreach ($arOrderPropertyValues as &$arPropertyValue): ?>
<tr>
	<td><?=$arPropertyValue['NAME']?></td>
	<td>
		<?switch ($arPropertyValue['PROPERTY_TYPE']) {
		case 'S':
		case 'N':
			?>
			<input type="text" name="PROPERTIES[<?=$arPropertyValue['PROPERTY_ID']?>]" value="<?=$arPropertyValue['VALUE']?>"/>
			<?break;
		case 'T':
			?>
			<textarea
					name="PROPERTIES[<?=$arPropertyValue['PROPERTY_ID']?>]"><?=$arPropertyValue['VALUE']?></textarea><?
			break;
		case 'L':
			?>
			<select name="PROPERTIES[<?=$arPropertyValue['PROPERTY_ID']?>]">
				<option value="null" <?if ($arPropertyValue['VALUE_ENUM_ID'] == null): ?>selected="selected"<? endif;?>>
					[null] Не выбрано
				</option>

				<?foreach ($arPropertyValue['PROPERTY_ENUM_VALUES'] as $arPropEnum): ?>
				<option<?if ($arPropEnum['ID'] == $arPropertyValue['VALUE_ENUM_ID']): ?>
						selected="selected"<? endif?>
						value="<?=$arPropEnum['CODE']?>">
					[<?=$arPropEnum['CODE']?>] <?=$arPropEnum['VALUE']?></option>
				<? endforeach;?>
			</select><?
			break;
		case 'C':
			?>
			<input type="hidden" name="PROPERTIES[<?=$arPropertyValue['PROPERTY_ID']?>]" value="N"/>
			<input type="checkbox" name="PROPERTIES[<?=$arPropertyValue['PROPERTY_ID']?>]"
				   value="Y" <?if ($arPropertyValue['VALUE'] == 'Y'): ?> checked="checked"<? endif?> />
			<?
			break;
	}?>
	</td>
</tr>
	<? endforeach;?>

<?$TabControl->BeginNextTab();?>
<tr>
	<td colspan="2">
		<table cellpadding="0" cellspacing="0" border="0" class="edit-tab-title">
			<tr>
				<td class="icon">
					<div id="sale"></div>
				</td>
				<td class="title">Состав заказа</td>
			</tr>
			<tr>
				<td colspan="2" class="delimiter">
					<div class="empty"></div>
				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2">
		<table id="items_list" class="internal" width="100%">
			<tr class="heading">
				<td>№</td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_NAME")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_PRICE_NAME")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_WEIGHT")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_QUANTITY")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_PRICE_VALUE")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_ACTIONS")?></td>
			</tr>
			<tr class="item-row" id="row_0" data-id="0" style="display:none;">
				<td colspan="7"></td>
			</tr>
			<?
			$i = 1;
			$summaryCost = 0;
			$summaryWeight = 0;
			$summaryQuant = 0;

			foreach ($arOrderItems as $arItem) {
				$summaryCost+= floatval($arItem['PRICE_VALUE']) * floatval($arItem['QUANTITY']);
				$summaryWeight+= floatval($arItem['WEIGHT']);
				$summaryQuant+= floatval($arItem['QUANTITY']);
				?>

				<tr class="item-row" id="row_<?=$i?>" data-id="<?=$i?>">
					<td><?=$i?></td>
					<td>
						<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbprodinput<?=$i?>">
							<tr>
								<td class="item-name">
									<span class="not noedit" id="sp_TABLE[<?=$i?>]" ><?=$arItem['PRODUCT_NAME']?></span>
									<input type="hidden" name="TABLE[<?=$i?>][PRODUCT_ID]" id="TABLE[<?=$i?>][PRODUCT_ID]" value="<?=$arItem['PRODUCT_ID']?>">
									<input type="button" class="change_button" style="display: none;" value="..."
											onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=0&amp;n=TABLE[<?=$i?>]&amp;k=PRODUCT_ID', 600, 500);">
								</td>
							</tr>
						</table>
					</td>
					<td>
						<select name=TABLE[<?=$i?>][PRICE_ID] class="not" disabled >
							<?foreach($arPriceTypes as $arPrice){?>
							<option value="<?=$arPrice['ID']?>" <?if($arItem['PRICE_ID'] == $arPrice['ID'] ){?>selected<?}?>><?=$arPrice['NAME']?></option>
							<?}?>
						</select>
					</td>
					<td>
						<input type="text" class="not" readonly name="TABLE[<?=$i?>][WEIGHT]" value="<?=$arItem['WEIGHT']?>">
					</td>
					<td>
						<input type="text" class="not" readonly name="TABLE[<?=$i?>][QUANTITY]" value="<?=$arItem['QUANTITY']?>">
					</td>
					<td align="right">
						<input type="text" class="not" readonly name="TABLE[<?=$i?>][PRICE_VALUE]" value="<?=$arItem['PRICE_VALUE']?>">
					</td>
					<td class="action-row" align="center">
						<a href="javascript:void(0)" class="delete_item" data="<?=$i?>"><?=GetMessage("OBX_ORDER_HREF_ACTION_DELETE")?></a>
						<br>
						<br>
						<a href="javascript:void(0)" class="edit_item" data="<?=$i?>"><?=GetMessage("OBX_ORDER_HREF_ACTION_EDIT")?></a>
						<input type="hidden" id="to_delete_<?=$i?>" name="TABLE[<?=$i?>][TO_DELETE]" value="N">
					</td>
				</tr>

				<?$i++;
			}?>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2" align="center">
		<input type="button" id="add_item" value="<?=GetMessage("OBX_ORDER_ACTION_ADD_PROD")?>" align="center">
	</td>
</tr>

<tr>
	<td>
		<table class="internal" style="width: 33%; float: right">
			<tr class="heading">
				<td colspan="2"><?=GetMessage("OBX_ORDER_ITEMS_SUMMARY")?></td>
			</tr>

			<tr>
				<td><?=GetMessage("OBX_ORDER_ITEMS_SUMMARY_COST")?></td>
				<td><?=$summaryCost?></td>
			</tr>
			<tr>
				<td><?=GetMessage("OBX_ORDER_ITEMS_SUMMARY_WEIGHT")?></td>
				<td><?=$summaryWeight?></td>
			</tr>
			<tr>
				<td><?=GetMessage("OBX_ORDER_ITEMS_SUMMARY_QUANTITY")?></td>
				<td><?=$summaryQuant?></td>
			</tr>
		</table>
	</td>
</tr>


<script type="x-template/custom" id="edit-href-template">
	<a href="javascript:void(0)" class="edit_item" data="#ID#"><?=GetMessage("OBX_ORDER_HREF_ACTION_EDIT")?></a>
</script>

<script type="x-template/custom" id="cancel-delete-item">
	<a href="javascript:void(0)" class="undelete_item" data="#ID#"><?=GetMessage("OBX_ORDER_HREF_ACTION_UNDELETE")?></a>
</script>

<script type="x-template/custom" id="start-delete-item">
	<a href="javascript:void(0)" class="delete_item" data="#ID#"><?=GetMessage("OBX_ORDER_HREF_ACTION_DELETE")?></a>
</script>

<script type="x-template/custom" id="products-row-template">
	<tr class="item-row" id="row_#ID#" data-id='#ID#'>
	<td>#ID#</td>
	<td>
		<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbprodinput#ID#">
			<tr>
				<td class="item-name">
					<span class="not noedit" id="sp_TABLE[#ID#]"> --- </span>
					<input type="hidden" name="TABLE[#ID#][PRODUCT_ID]" id="TABLE[#ID#][PRODUCT_ID]" value="NULL">
					<input type="button" class="change_button" value="..."
						   onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=0&amp;n=TABLE[#ID#]&amp;k=PRODUCT_ID', 600, 500);">
				</td>
			</tr>
		</table>
	</td>
	<td>
		<select name=TABLE[#ID#][PRICE_ID]>
			<?foreach($arPriceTypes as $arPrice){?>
				<option value="<?=$arPrice['ID']?>"><?=$arPrice['NAME']?></option>
			<?}?>
		</select>
	</td>
	<td>
		<input type='text' name='TABLE[#ID#][WEIGHT]' value='0.00'>
	</td>
	<td>
		<input type='text' name='TABLE[#ID#][QUANTITY]' value='0'>
	</td>
	<td align='right'>
		<input type='text' name='TABLE[#ID#][PRICE_VALUE]' value='0'>
	</td>
	<td class='action-row' align='center'>
		<a href="javascript:void(0)" class="delete_item" data="#ID#"><?=GetMessage("OBX_ORDER_HREF_ACTION_DELETE")?></a>
		<br>
		<br>
		<input type="hidden" id="to_delete_#ID#" name="TABLE[#ID#][TO_DELETE]" value="N">
	</td>
	</tr>
</script>

<script type="text/javascript">
	if (typeof(jQuery) == 'undefined') jQuery = false;
	(function ($) {
		var rowtempl = $("#products-row-template").html();
		var editHrefTempl = $("#edit-href-template").html();
		var cancelDeleteHrefTempl = $("#cancel-delete-item").html();
		var startDeleteHrefTempl = $("#start-delete-item").html();

		$("#add_item").on("click",function(){
			var $lastrow = $("#items_list tr.item-row:last-child");
			var newid = Number($lastrow.attr("data-id"))+1;
			var newtempl = rowtempl.replace(/\#ID\#/g,newid);
			$lastrow.after(newtempl);
		});
		$("#items_list").on("click",".edit_item",function(){
			var $this = $(this);
			var dataID = $this.attr("data");
			var $thisRow = $("#items_list #row_"+dataID);

			$thisRow.find("input[type=text].noedit").removeClass("not");

			var $inputText = $thisRow.find("input[type=text]:not(.noedit)");
			var $selects = $thisRow.find("select");

			$inputText.removeClass("not");
			$inputText.removeAttr("readonly");
			$selects.removeClass("not");
			$selects.removeAttr("disabled");

			$thisRow.find(".change_button").show();

			$this.remove();
		});

		$("#items_list").on("click", ".delete_item", function(){
			var $this = $(this);
			var id = $this.attr("data");
			var $thisRow = $("#items_list #row_"+id);

			$thisRow.find(".edit_item").hide();
			$thisRow.find("#to_delete_"+id).val("Y");
			$thisRow.addClass("deleted");

			$this.after(cancelDeleteHrefTempl.replace(/\#ID\#/g,id));
			$this.remove();
		});

		$("#items_list").on("click",".undelete_item",function(){
			var $this = $(this);
			var id = $this.attr("data");
			var $thisRow = $("#items_list #row_"+id);

			$thisRow.find(".edit_item").show();
			$thisRow.find(".to_delete_"+id).val("N");
			$thisRow.removeClass("deleted");

			$this.after(startDeleteHrefTempl.replace(/\#ID\#/g,id));
			$this.remove();
		});

	})(jQuery)
</script>
<?
$TabControl->Buttons(
	array(
		"disabled" => false,
		"back_url" => "obx_market_orders.php",
	)
);
$TabControl->End();
?>
</form>
<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
?>

<?/*
<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tb7195fd7094d305b4987af966f093b6a7">
	<tr>
		<td>
			<input name="PROP[17][n0]" id="PROP[17][n0]" value="" size="5" type="text">
			<input type="button" value="..."
				   onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=0&amp;n=PROP[17]&amp;k=n0', 600, 500);">&nbsp;
			<span id="sp_7195fd7094d305b4987af966f093b6a7_n0"></span>
		</td>
	</tr>
</table>

<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbprodinput<?=$i?>">
	<tr>
		<td>
			<input name="TABLE[<?=$i?>][PRODUCT_ID]" id="TABLE[<?=$i?>][PRODUCT_ID]" value="" size="5" type="text">
			<input type="button" value="..."
				   onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=0&amp;n=TABLE[<?=$i?>]&amp;k=<?=$i?>', 600, 500);">
			<span id="sp_prodinput_<?=$i?>"></span>
		</td>
	</tr>
</table>
*/ ?>
