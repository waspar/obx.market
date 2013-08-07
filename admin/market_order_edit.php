<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/
use OBX\Market\Currency;
use OBX\Market\CurrencyDBS;
use OBX\Market\CurrencyFormat;
use OBX\Market\CurrencyFormatDBS;
use OBX\Market\Order;
use OBX\Market\OrderDBS;
use OBX\Market\OrderStatusDBS;
use OBX\Market\OrderPropertyDBS;
use OBX\Market\OrderPropertyValuesDBS;
use OBX\Market\OrderPropertyEnumDBS;
use OBX\Market\Price;
use OBX\Market\PriceDBS;

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

$APPLICATION->AddHeadScript("/bitrix/js/obx.market/jquery-1.9.1.min.js");

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

$Order = Order::getOrder($ID, $arError);

if (!empty($arError)) {
	$arErrors[] = $arError;
}

$arStatusList = OrderStatusDBS::getInstance()->getListArray();
$arCurrencyFormatList = CurrencyFormatDBS::getInstance()->getListGroupedByLang();
$currencyDefault = CurrencyDBS::getInstance()->getDefault();

$OrderDBS = OrderDBS::getInstance();
$OrderPropertyEnumDBS = OrderPropertyEnumDBS::getInstance();

$arOrder = array();
$arOrderStatus = array();
$arOrderPropertyValues = array();
$arPriceTypes = PriceDBS::getInstance()->getListArray();

if ($REQUEST_METHOD == "POST" // проверка метода вызова страницы
	&& ($save != "" || $apply != "") // проверка нажатия кнопок "Сохранить" и "Применить"
	//&& $POST_RIGHT == "W" // проверка наличия прав на запись для модуля
	&& check_bitrix_sessid() // проверка идентификатора сессии
) {
	$DB->StartTransaction();
	$arItems = $_REQUEST['PRODUCTS'];
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
				if(!$bSuccess) {
					$arErrorList = $Order->getErrors();
					foreach($arErrorList as $arError) {
						$arErrors[] = $arError['TEXT'].' code: '.$arError['CODE'];
					}
				}
			}
			if (!empty($arProps)) {
				$bSuccess = $Order->setProperties($arProps);
			}
		}

	} else {

		$newID = $OrderDBS->add($_REQUEST['FIELDS']);
		$bSuccess = ($newID > 0) ? true : false;
		if ($bSuccess) {
			$ID = $newID;
			$Order = Order::getOrder($ID, $arError);
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
		$arOrderErrorsPool = $Order->getErrors();
		foreach($arOrderErrorsPool as $arOrderError) {
			$arErrors[] = $arOrderError['TEXT'];
		} unset($arOrderErrorsPool, $arOrderError);
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
	$OrderPropertyDBS = OrderPropertyDBS::getInstance();
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
					<?=GetMessage('OBX_MARKET_ORDER_EDIT_NOT_SELECTED')?>
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
				<td class="title"><?=GetMessage('OBX_MARKET_ORDER_ITEMS_EDIT_TAB')?> <input type="button" class="add_item right" value="<?=GetMessage("OBX_ORDER_ACTION_ADD_PROD")?>" align="right"></td>
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
				<td><?=GetMessage('NUM_CHAR')?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_NAME")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_WEIGHT")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_QUANTITY")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_PRICE_NAME")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_PRICE_VALUE")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_DISCOUNT_VALUE")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_PRODUCT_TOTAL_VALUE")?></td>
				<td><?=GetMessage("OBX_ORDER_TITLE_ACTIONS")?></td>
			</tr>
			<tr class="item-row" id="row_0" data-num="0" style="display:none;">
				<td colspan="7"></td>
			</tr>
			<?
			$i = 1;
			$summaryCost = 0;
			$summaryWeight = 0;
			$summaryQuant = 0;
			$arItemsInOrderJSON = array();

			foreach ($arOrderItems as $arItem) {
				$summaryCost+= floatval($arItem['PRICE_VALUE']) * floatval($arItem['QUANTITY']);
				$summaryWeight+= floatval($arItem['WEIGHT']);
				$summaryQuant+= floatval($arItem['QUANTITY']);
				$arItemsInOrderJSON[$arItem['PRODUCT_ID']] = floatVal($arItem['QUANTITY']);
				?>

				<tr class="item-row" id="row_<?=$i?>" data-num="<?=$i?>" data-product-id="<?=$arItem['PRODUCT_ID']?>" data-price-id="<?=$arItem['PRICE_ID']?>">
					<td><?=$i?></td>
					<td>
						<input class="id" type="hidden" name="PRODUCTS[<?=$i?>][ID]" id="PRODUCTS[<?=$i?>][ID]" value="<?=$arItem['ID']?>" />
						<input class="product_id" type="hidden"  name="PRODUCTS[<?=$i?>][PRODUCT_ID]" id="PRODUCTS[<?=$i?>][PRODUCT_ID]" value="<?=$arItem['PRODUCT_ID']?>">
						<input class="product_name" type="text" name="PRODUCTS[<?=$i?>][PRODUCT_NAME]" value="<?=$arItem['PRODUCT_NAME']?>" />
					</td>
					<td>
						<input type="text" class="weight" size="5" name="PRODUCTS[<?=$i?>][WEIGHT]" value="<?=$arItem['WEIGHT']?>">
					</td>
					<td>
						<input type="text" class="quantity" size="5" name="PRODUCTS[<?=$i?>][QUANTITY]" value="<?=$arItem['QUANTITY']?>">
					</td>
					<td>
						<select class="price_id_view" name=PRODUCTS[<?=$i?>][PRICE_ID] disabled="disabled">
							<option value="null"><?=GetMessage('OBX_ORDER_TITLE_PRODUCT_PRICE_DEFAULT')?></option>
							<?foreach($arPriceTypes as $arPrice){?>
								<option value="<?=$arPrice['ID']?>" <?if($arItem['PRICE_ID'] == $arPrice['ID'] ){?>selected<?}?>><?=$arPrice['NAME']?></option>
							<?}?>
						</select>
						<input type="hidden" class="price_id" name=PRODUCTS[<?=$i?>][PRICE_ID] />
					</td>
					<td align="right">
						<input type="text" class="price_value" size="6" name="PRODUCTS[<?=$i?>][PRICE_VALUE]" value="<?=$arItem['PRICE_VALUE']?>">
						<input type="hidden" class="price_id" name="PRODUCTS[<?=$i?>][PRICE_ID]" value="<?=$arItem['PRICE_ID']?>"
					</td>
					<td>
						<input type="text" size="5" class="discount_value" name="PRODUCTS[<?=$i?>][DISCOUNT_VALUE]" value="<?=$arItem['DISCOUNT_VALUE']?>" />
					</td>
					<td>
						<input type="text" size="5" class="total_price_value" name="PRODUCTS[<?=$i?>][TOTAL_PRICE_VALUE]" value="<?=$arItem['TOTAL_PRICE_VALUE']?>" disabled="disabled" />
					</td>
					<td class="action-row" align="center">
						<a href="javascript:void(0)" class="delete_item" data-num="<?=$i?>"><?=GetMessage("OBX_ORDER_HREF_ACTION_DELETE")?></a>
						<input type="hidden" id="to_delete_<?=$i?>" name="PRODUCTS[<?=$i?>][TO_DELETE]" value="N">
					</td>
				</tr>

				<?$i++;
			}?>
		</table>
	</td>
</tr>
<tr>
	<td colspan="2" align="center">
		<input type="button" class="add_item" value="<?=GetMessage("OBX_ORDER_ACTION_ADD_PROD")?>" align="center">
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
	<a href="javascript:void(0)" class="edit_item" data-num="#NUM#"><?=GetMessage("OBX_ORDER_HREF_ACTION_EDIT")?></a>
</script>

<script type="x-template/custom" id="cancel-delete-item">
	<a href="javascript:void(0)" class="undelete_item" data-num="#NUM#"><?=GetMessage("OBX_ORDER_HREF_ACTION_UNDELETE")?></a>
</script>

<script type="x-template/custom" id="start-delete-item">
	<a href="javascript:void(0)" class="delete_item" data-num="#NUM#"><?=GetMessage("OBX_ORDER_HREF_ACTION_DELETE")?></a>
</script>

<script type="x-template/custom" id="products-row-template">
	<tr class="item-row" id="row_#NUM#" data-num='#NUM#'>
	<td>#NUM#</td>
	<td>
		<table cellpadding="0" cellspacing="0" border="0" class="nopadding" width="100%" id="tbprodinput#ID#">
			<tr>
				<td class="item-name">
					<input class="id" type="hidden"  name="PRODUCTS[#NUM#][ID]" id="PRODUCTS[#NUM#][ID]" />
					<input class="product_id" type="hidden"  name="PRODUCTS[#NUM#][PRODUCT_ID]" id="PRODUCTS[#NUM#][PRODUCT_ID]" />
					<input class="product_name" type="text" name="PRODUCTS[#NUM#][PRODUCT_NAME]" />

				</td>
			</tr>
		</table>
	</td>
	<td>
		<input type='text' class="weight" size="5" name="PRODUCTS[#NUM#][WEIGHT]" value="0.00">
	</td>
	<td>
		<input type='text' class="quantity" size="5" name='PRODUCTS[#NUM#][QUANTITY]' value='0'>
	</td>
	<td>
		<select name="PRODUCTS[#NUM#][PRICE_ID]" class="price_id_view" disabled="disabled">
			<option value="null" selected="selected"><?=GetMessage('OBX_ORDER_TITLE_PRODUCT_PRICE_DEFAULT')?></option>
			<?foreach($arPriceTypes as $arPrice){?>
				<option value="<?=$arPrice['ID']?>"><?=$arPrice['NAME']?></option>
			<?}?>
		</select>
		<input type="hidden" class="price_id" name=PRODUCTS[#NUM#][PRICE_ID] />
	</td>
	<td align="right">
		<input type='text' class="price_value" name="PRODUCTS[#NUM#][PRICE_VALUE]" value="0" />
	</td>
	<td>
		<input type='text' class="discount_value" name="PRODUCTS[#NUM#][DISCOUNT_VALUE]" value="0" />
	</td>
	<td>
		<input type='text' class="total_price_value" name="PRODUCTS[#NUM#][TOTAL_PRICE_VALUE]" disabled="disabled" value="0" />
	</td>
	<td class="action-row" align="center">
		<a href="javascript:void(0)" class="delete_item" data-num="#NUM#"><?=GetMessage("OBX_ORDER_HREF_ACTION_DELETE")?></a>
		<br>
		<br>
		<input type="hidden" id="to_delete_#NUM#" name="PRODUCTS[#NUM#][TO_DELETE]" value="N" />
	</td>
	</tr>
</script>

<script type="text/javascript">
	if( typeof(obx) == 'undefined' ) { obx = {}; }
	if( typeof(obx.admin) == 'undefined' ) { obx.admin = {}; }
	if( typeof(obx.admin.order_items) == 'undefined' ) { obx.admin.order_items = {}; }
	obx.admin.order_items.list = <?=json_encode($arItemsInOrderJSON, JSON_FORCE_OBJECT)?>;
	(function ($) {
		if (typeof($) == 'undefined') return false;
		var rowtempl = $("#products-row-template").html();
		var editHrefTempl = $("#edit-href-template").html();
		var cancelDeleteHrefTempl = $("#cancel-delete-item").html();
		var startDeleteHrefTempl = $("#start-delete-item").html();



		obx.admin.order_items.addNewRow = function() {
			var $lastrow = $("#items_list tr.item-row:last-child");
			var newRowNum = Number($lastrow.attr("data-num"))+1;
			var newtempl = rowtempl.replace(/#NUM#/g, newRowNum);
			$lastrow.after(newtempl);
			return newRowNum;
		}
		obx.admin.order_items.addProductToOrder = function(oItem, domAddButton) {
			if( typeof(oItem.product_id) == 'undefined' || oItem.product_id <1 ) return false;
			if( typeof(oItem.price_id) == 'undefined' || oItem.price_id <1) return false;
			if( typeof(oItem.price_value) == 'undefined' || oItem.price_value < 0) return false;
			if( typeof(oItem.weight) == 'undefined' || oItem.weight <= 0) oItem.weight = 0.00;
			if( typeof(oItem.quantity) == 'undefined' || oItem.quantity <= 0) oItem.quantity = 1;
			if( typeof(oItem.name) == 'undefined' ) oItem.name = 'New product: ' + oItem.product_id;
			//find exists row
			var $itemRow = $('tr.item-row[data-product-id="'+oItem.product_id+'"][data-price-id="'+oItem.price_id+'"]');
			if($itemRow.length < 1) {
				var newRowID = obx.admin.order_items.addNewRow();
				$itemRow = $('tr[data-num='+newRowID+']')
			}
			if($itemRow.length < 1) {
				return false;
			}
			var listKey = oItem.product_id+'_'+oItem.price_id;
			if( typeof(obx.admin.order_items.list[listKey]) == 'undefined' ) {
				obx.admin.order_items.list[listKey] = parseFloat(oItem.quantity);
			}
			else {
				obx.admin.order_items.list[listKey] += parseFloat(oItem.quantity);
			}
			var $selectPriceID = $itemRow.find('select.price_id_view');
			var $selectedOptionPriceID = $selectPriceID.find('option[value='+oItem.price_id+']');
			if($selectedOptionPriceID.length > 0) {
				$selectPriceID.find('options').removeAttr('selected');
				$selectedOptionPriceID.attr('selected', 'selected');
			}
			var $inputPriceID = $itemRow.find('input[type=hidden].price_id');
			var $inputProductID = $itemRow.find('input[type=hidden].product_id').attr('value', oItem.product_id);
			var $inputWeight = $itemRow.find('input.weight');
			var $inputPriceValue = $itemRow.find('input.price_value');
			var $inputQuantity = $itemRow.find('input.quantity');
			var $inputName = $itemRow.find('input.product_name');

			$itemRow.attr('data-product-id', oItem.product_id);
			$itemRow.attr('data-price-id', oItem.price_id);
			$inputName[0].value = oItem.name;
			$inputProductID[0].value = oItem.product_id;
			$inputPriceValue[0].value = oItem.price_value;
			$inputQuantity[0].value = obx.admin.order_items.list[listKey];
			$inputWeight[0].value = oItem.weight;
			$inputPriceID[0].value = oItem.price_id;

			var $domAddButton = $(domAddButton);
			if( typeof($domAddButton.is('input[type=button]')) ) {
				$domAddButton.attr('value', '<?=GetMessage('OBX_ORDER_PRODUCT_SEARCH_SELECTED_BUTTON')?>: '
											+ obx.admin.order_items.list[listKey]);
			}
			else if( typeof($domAddButton.is('button')) ) {
				$domAddButton.text('<?=GetMessage('OBX_ORDER_PRODUCT_SEARCH_SELECTED_BUTTON')?>: '
										+ obx.admin.order_items.list[listKey]);
			}
		};


		$("input[type=button].add_item").on("click",function(){
			jsUtils.OpenWindow('/bitrix/admin/obx_market_product_search.php?lang=ru&amp;IBLOCK_ID=0&amp;&amp;k=PRODUCT_ID', 800, 600);
		});

		$("#items_list").on("click", ".delete_item", function(){
			var $this = $(this);
			var num = $this.attr("data-num");
			var $thisRow = $("#items_list #row_"+num);

			$thisRow.find(".edit_item").hide();
			$thisRow.find("#to_delete_"+num).val("Y");
			$thisRow.addClass("deleted");

			$this.after(cancelDeleteHrefTempl.replace(/#NUM#/g, num));
			$this.remove();
		});

		$("#items_list").on("click",".undelete_item",function(){
			var $this = $(this);
			var num = $this.attr("data-num");
			var $thisRow = $("#items_list #row_"+num);

			$thisRow.find(".edit_item").show();
			$thisRow.find(".to_delete_"+num).val("N");
			$thisRow.removeClass("deleted");

			$this.after(startDeleteHrefTempl.replace(/\#ID\#/g,num));
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
			<input name="PRODUCTS[<?=$i?>][PRODUCT_ID]" id="PRODUCTS[<?=$i?>][PRODUCT_ID]" value="" size="5" type="text">
			<input type="button" value="..."
				   onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=ru&amp;IBLOCK_ID=0&amp;n=PRODUCTS[<?=$i?>]&amp;k=<?=$i?>', 600, 500);">
			<span id="sp_prodinput_<?=$i?>"></span>
		</td>
	</tr>
</table>
*/ ?>
