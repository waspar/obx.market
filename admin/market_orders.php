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

use OBX\Market\CurrencyFormatDBS;
use OBX\Market\CurrencyFormatDBS as OBX_CurrencyFormatDBS;
use OBX\Market\Order;
use OBX\Market\OrderDBS;
use OBX\Market\Order as OBX_Order;
use OBX\Market\OrderDBS as OBX_OrderDBS;
use OBX\Market\OrderStatusDBS;
use OBX\Market\OrderStatusDBS as OBX_OrderStatusDBS;
use OBX\Market\OrderPropertyDBS;
use OBX\Market\OrderPropertyDBS as OBX_OrderPropertyDBS;
use OBX\Market\OrderPropertyValuesDBS;
use OBX\Market\OrderPropertyValuesDBS as OBX_OrderPropertyValuesDBS;
use OBX\Market\OrderPropertyEnumDBS;
use OBX\Market\OrderPropertyEnumDBS as OBX_OrderPropertyEnumDBS;

/*
 * TODO: Сейчас свойства и статусы работают на позапросах внутри цикла. Это исправимо. Займемся позже. надо переделать под класс OBX_Order
 *
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
if(!CModule::IncludeModule('obx.market')) return;

$APPLICATION->AddHeadString('<meta http-equiv="X-UA-Compatible" content="IE=edge">');
// Доступ
//if (!$USER->CanDoOperation('edit_orders'))
//	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
//$isAdmin = $USER->CanDoOperation('edit_orders');
if (!$USER->IsAdmin()) {
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}

IncludeModuleLangFile(__FILE__);
?>
<?

$tableID = 'tbl_obx_orders';
$OrderDBS = OBX_OrderDBS::getInstance();
$OrderStatusDBS = OBX_OrderStatusDBS::getInstance();
$OrderPropertyDBS = OBX_OrderPropertyDBS::getInstance();
$OrderPropertyValuesDBS = OBX_OrderPropertyValuesDBS::getInstance();
$OrderPropertyEnumDBS = OBX_OrderPropertyEnumDBS::getInstance();
$CurrencyFormatDBS = OBX_CurrencyFormatDBS::getInstance();
$oSort = new CAdminSorting($tableID, 'SORT', 'ASC');
$lAdmin = new CAdminList($tableID, $oSort);

$arOrderStatusListRaw = $OrderStatusDBS->getListArray();
$arOrderStatusList = array();
?>
<style type="text/css">
<?
foreach($arOrderStatusListRaw as &$arOrderStatus) {
	$arOrderStatusList[$arOrderStatus['ID']] = $arOrderStatus;
	if (!empty($arOrderStatus["COLOR"])){
	?>
	#tbl_obx_orders tr[ondblclick*="statusID=<?=$arOrderStatus['ID']?>"] td{
		background: #<?=$arOrderStatus["COLOR"]?>;
	}
	<?}?>
<?}?>
</style>
<?
unset($arOrderStatusListRaw);

$arCurrencyList = $CurrencyFormatDBS->getListGroupedByLang();

$arOrderPropertiesRaw = $OrderPropertyDBS->getListArray();
$arOrderProperties = array();
foreach($arOrderPropertiesRaw as &$arOrderProperty) {
	$arOrderProperties[$arOrderProperty['ID']] = $arOrderProperty;
}
unset($arOrderPropertiesRaw);

if(!defined('___CheckFilter_DEFINED')) {
	define('___CheckFilter_DEFINED', true);
	function ___CheckFilter(){
		global $FilterArr, $lAdmin;
		foreach ($FilterArr as $f) global $$f;
		return count($lAdmin->arFilterErrors)==0;
	}
}

/**
 * Фильр
 */
//$lAdmin->InitFilter(array(
//	'find_ID',
//	'find_NAME',
//	'find_CODE',
//));
//// Фильтр
//$arFilter = Array();
//if(___CheckFilter()) {
//	$arFilter = Array(
//		'CODE'   => $find_CODE,
//		'NAME'   => $find_NAME,
//		'ID'   => $find_ID,
//	);
//}

/**
 * Обработка
 */
if($lAdmin->EditAction()){
	foreach($FIELDS as $ID => &$arFields) {
		$ID = IntVal($ID);
		if($ID <= 0) {
			continue;
		}
		$arUpdateFields = array(
			'ID' => $ID
		);
		foreach($arFields as $fldName => &$fldValue) {
			if($fldValue != $FIELDS_OLD[$ID][$fldName]) {
				$arUpdateFields[$fldName] = $fldValue;
			}
		}
		if( count($arUpdateFields)>1 ) {
			if( !$OrderDBS->update($arUpdateFields) ) {
				$lAdmin->AddUpdateError($OrderDBS->popLastError(), $ID);
			}
		}
	}
	if( count($PROPERTIES)>0 ) {
		$DB->StartTransaction();
		foreach($PROPERTIES as $ID => &$arPropertyValuesList) {
			foreach($arPropertyValuesList as $propertyID => $propertyValue) {
				if( !isset($arOrderProperties[$propertyID]) ) {
					continue;
				}
				$arProperty = $arOrderProperties[$propertyID];
				$arPropUpdateFields = array(
					'ORDER_ID' => $ID,
					'PROPERTY_ID' => $arProperty['ID'],
				);
				$bDoUpdatePropValue = true;
				$bCreateNewPropValue = false;
				if( $arProperty['PROPERTY_TYPE'] == 'S' ) {
					$arPropUpdateFields['VALUE_S'] = $propertyValue;
				}
				elseif( $arProperty['PROPERTY_TYPE'] == 'N' ) {
					$arPropUpdateFields['VALUE_N'] = $propertyValue;
				}
				elseif( $arProperty['PROPERTY_TYPE'] == 'T' ) {
					$arPropUpdateFields['VALUE_T'] = $propertyValue;
				}
				elseif( $arProperty['PROPERTY_TYPE'] == 'L' ) {
					$arPropUpdateFields['VALUE_L'] = $propertyValue;
				}
				elseif( $arProperty['PROPERTY_TYPE'] == 'C' ) {
					$arPropUpdateFields['VALUE_C'] = $propertyValue;
				}
				else {
					$bDoUpdatePropValue = false;
				}
				// check value exists
				$arExistsPropValueLst = $OrderPropertyValuesDBS->getListArray(null, array(
					'ORDER_ID' => $ID,
					'PROPERTY_ID' => $propertyID,
				));
				if( !isset($arExistsPropValueLst[0]['ID']) || intval($arExistsPropValueLst[0]['ID'])<=0 ) {
					$bDoUpdatePropValue = false;
					$bCreateNewPropValue = true;
				}
				$arPropUpdateFields['ID'] = $arExistsPropValueLst[0]['ID'];
				$bSuccess = true;
				if($bDoUpdatePropValue) {
					$bSuccess = $OrderPropertyValuesDBS->update($arPropUpdateFields);
				}
				elseif($bCreateNewPropValue) {
					$newPropValueID = $OrderPropertyValuesDBS->add($arPropUpdateFields);
					$bSuccess = (intval($newPropValueID)>0)?true:false;
				}
				if( !$bSuccess ) {
					$lAdmin->AddUpdateError($OrderPropertyValuesDBS->popLastError(), $ID);
				}
			}
		}
		$DB->Commit();
	}
}
if( ($arID = $lAdmin->GroupAction()) ) {
	if(false && $_REQUEST['action_target']=='selected'){
		$rsData = $OrderDBS->getList(array($by=>$objOrder), $arFilter);
		while($arRes = $rsData->Fetch())
			$arID[] = $arRes['ID'];
	}

	foreach($arID as $ID){
		$ID = IntVal($ID);
		if($ID <= 0)
			continue;
		switch($_REQUEST['action'])
		{
			case 'delete':
				@set_time_limit(0);
				$DB->StartTransaction();
				if (!$OrderDBS->delete($ID)){
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage('OBX_STATUS_DEL_ERROR_1'), $ID);
				}
				$DB->Commit();
				break;
		}

		if (strpos($_REQUEST["action"],'setstatus')!==false){
			$arAction = explode("_",$_REQUEST["action"]);
			if (count($arAction) >1){
				@set_time_limit(0);
				$DB->StartTransaction();

				$statusID = intval($arAction[1]);
				if(!OBX_Order::getOrder($ID)->setStatus($statusID)){
					$DB->Rollback();
					$lAdmin->AddGroupError(GetMessage('OBX_STATUS_CHANGE_ERROR_1'), $ID);
				}
				$DB->Commit();
			}
		}
	}
}


/**
 * Выборка
 */
$rsData = $OrderDBS->getList(array($by=>$order), $arFilter, null, null, array(
	'ID', 'USER_ID', 'STATUS_ID', 'DATE_CREATED', 'TIMESTAMP_X', 'CURRENCY', 'ITEMS_COST', 'ITEMS_JSON', 'PROPERTIES_JSON'
));
$rsData = new CAdminResult($rsData, $tableID);

$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage('OBX_MARKET_ORDERS_LIST_NAV')));

// Заголовки
$aHeaders = array(
	array('id'=>'ID', 'content'=>'ID', 'sort'=>'ID', 'default'=>true),
	array('id'=>'USER_ID', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_USER'), 'default'=>true),
	array('id'=>'STATUS_ID', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_STATUS'), 'sort'=>'STATUS_ID', 'default'=>true),
	array('id'=>'DATE_CREATED', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_CREATED'), 'sort'=>'DATE_CREATED', 'default'=>true),
	array('id'=>'TIMESTAMP_X', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_TIMESTAMP_X'), 'sort'=>'TIMESTAMP_X', 'default'=>true),
	array('id'=>'CURRENCY', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_CURRENCY'), 'sort'=>'CURRENCY', 'default'=>true),
	array('id'=>'COST', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_COST'), 'sort'=>'ITEMS_COST', 'default'=>true),
	array('id'=>'ITEMS_JSON', 'content'=>GetMessage('OBX_MARKET_ORDERS_F_ITEMS'), 'default'=>true),
	array('id'=>'PROPERTIES_JSON', 'content'=> GetMessage('OBX_MARKET_ORDERS_F_PROPERTIES_JSON'), 'default'=>false),
);

foreach($arOrderProperties as $propertyID => &$arProperty) {
	$aHeaders[] = array('id'=>'PROPERTY_'.$propertyID, 'content' => $arProperty['NAME'], 'default'=>false);
}

$lAdmin->AddHeaders($aHeaders);
// Обработка строк
while( $arRes = $rsData->NavNext(true, 'f_') ) {
	$row =& $lAdmin->AddRow($f_ID, $arRes,"obx_market_order_edit.php?ID=".$f_ID."&#39;+/*&#39;#statusID=".$f_STATUS_ID.'*/&#39;',"изменить.");

	$row->AddViewField('USER_ID', '['.$f_USER_ID.']&nbsp;'.$f_USER_NAME);

	$row->AddViewField('STATUS_ID', '['.$f_STATUS_ID.']&nbsp;'.$arOrderStatusList[$f_STATUS_ID]['NAME']);
	$arOrderStatusList4Select = array();
	foreach($arOrderStatusList as &$arStatus) {
		$arOrderStatusList4Select[$arStatus['ID']] = '['.$arStatus['ID'].']&nbsp;'.$arStatus['NAME'];
	}
	$row->AddSelectField('STATUS_ID', $arOrderStatusList4Select);
	$row->AddViewField('CURRENCY', $arCurrencyList[$f_CURRENCY]['LANG'][LANGUAGE_ID]['NAME']);
	$row->AddViewField("COST", ($f_DELIVERY_COST + $f_ITEMS_COST + $f_PAY_TAX_VALUE - $f_DISCOUNT_VALUE));


	$itemsView = '';
	if(floatval($f_ITEMS_COST) > 0) {
		$arItemsFromJSON = json_decode(htmlspecialcharsback($f_ITEMS_JSON), true);
		if(!empty($arItemsFromJSON)) {
			foreach($arItemsFromJSON['items'] as &$arItemFromJSON) {
				$itemsView .= '<b>'.$arItemFromJSON['PRODUCT_NAME']
							.'&nbsp;('.$arItemFromJSON['PRODUCT_ID'].'):'
							.'&nbsp;'.$arItemFromJSON['QUANTITY']
							.GetMessage('OBX_MARKET_ORDER_LIST_UNIT')."<hr />\n";
			}
		}
		$debug=1;
	}
	$row->AddViewField("ITEMS_JSON", $itemsView);

	$arPropertyValues = json_decode(htmlspecialcharsback($f_PROPERTIES_JSON), true);
	$propertyView = '';
	foreach($arPropertyValues as &$arPropValue) {
		if($arPropValue['TYPE'] == 'C') {
			$arPropValue['VALUE'] = ($arPropValue['VALUE']=='Y')?GetMessage('YES'):GetMessage('NO');
		}
		$propertyView .= $arPropValue['NAME'].': <b>'.$arPropValue['VALUE']."</b><hr /><br />";
	}
	$row->AddViewField("PROPERTIES_JSON", $propertyView);

	$arPropValues = $OrderPropertyValuesDBS->getListArray(
		null,
		array(
			'ORDER_ID' => $f_ID
		),
		null,
		null,
		array(
			'ID', 'ORDER_ID', 'PROPERTY_ID', 'PROPERTY_TYPE', 'PROPERTY_NAME'
			,'VALUE', 'VALUE_L', 'VALUE_ENUM_CODE', 'VALUE_C'
		)
	);
	foreach($arPropValues as &$arPropValue) {
		if($arPropValue['PROPERTY_TYPE'] == 'L') {
			$row->AddViewField('PROPERTY_'.$arPropValue['PROPERTY_ID'], '['.$arPropValue['VALUE_ENUM_CODE'].'] '.$arPropValue['VALUE']);
			$arEnumValuesListRaw = $OrderPropertyEnumDBS->getListArray(null, array(
				'PROPERTY_ID' => $arPropValue['PROPERTY_ID'],
				'ORDER_ID' => $arPropValue['ORDER_ID'],
			));
			$sSelectHTML = '<select name="PROPERTIES['.$arPropValue['ORDER_ID'].']['.$arPropValue['PROPERTY_ID'].']">';
			foreach($arEnumValuesListRaw as $arEnumValue) {
				$sSelected = '';
				if($arEnumValue['ID'] == $arPropValue['VALUE_L']) {
					$sSelected = ' selected="selected"';
				}
				$sSelectHTML .= '<option value="'.$arEnumValue['CODE'].'"'.$sSelected.'>'.$arEnumValue['VALUE'].'</option>';
			}
			$sSelectHTML .= '</'.'select>';

			$row->AddEditField(
				'PROPERTY_'.$arPropValue['PROPERTY_ID'],
				$sSelectHTML
			);
		}
		elseif($arPropValue['PROPERTY_TYPE'] == 'S' || $arPropValue['PROPERTY_TYPE'] == 'N') {
			$row->AddViewField('PROPERTY_'.$arPropValue['PROPERTY_ID'], $arPropValue['VALUE']);
			$row->AddEditField(
				'PROPERTY_'.$arPropValue['PROPERTY_ID'],
				'<input type="text"'
					.' name="PROPERTIES['.$arPropValue['ORDER_ID'].']['.$arPropValue['PROPERTY_ID'].']"'
					.' value="'.$arPropValue['VALUE'].'"'
				.' />'
			);
		}
		elseif($arPropValue['PROPERTY_TYPE'] == 'T') {
			$row->AddViewField('PROPERTY_'.$arPropValue['PROPERTY_ID'], $arPropValue['VALUE']);
			$row->AddEditField(
				'PROPERTY_'.$arPropValue['PROPERTY_ID'],
				'<textarea'
					.' name="PROPERTIES['.$arPropValue['ORDER_ID'].']['.$arPropValue['PROPERTY_ID'].']"'
					.' rows=3 cols=25'
				.'>'.
						$arPropValue['VALUE']
				.'</textarea>'
			);
		}
		elseif($arPropValue['PROPERTY_TYPE'] == 'C') {
			$row->AddViewField('PROPERTY_'.$arPropValue['PROPERTY_ID'], ($arPropValue['VALUE']=='Y')?GetMessage('YES'):GetMessage('NO'));
			$row->AddEditField(
				'PROPERTY_'.$arPropValue['PROPERTY_ID'],
				'<input type="hidden"'
					.' name="PROPERTIES['.$arPropValue['ORDER_ID'].']['.$arPropValue['PROPERTY_ID'].']"'
					.' value="N"'
					.' />'
				.'<input type="checkbox"'
					.' name="PROPERTIES['.$arPropValue['ORDER_ID'].']['.$arPropValue['PROPERTY_ID'].']"'
					.(($arPropValue['VALUE_C']=='Y')?' checked="checked"':'')
					.' value="Y"'
					.' />'
			);
		}
	}


	// Меню строки
	$arActions = Array();
	$arActions[] = array(
		'ICON' => 'obx-order-list-edit',
		'TEXT' => GetMessage('OBX_ORDER_LIST_EDIT'),
		'ACTION' => $lAdmin->ActionRedirect('obx_market_order_edit.php?ID='.$f_ID),
		'DEFAULT' => 'Y',
	);
	$arActions[] = array(
		'SEPARATOR'=>true,
	);
	foreach($arOrderStatusList as &$arStatus) {
		$arActions[] = array(
			//'ICON' => 'status_completed',
			'TEXT' => 'Статус: ['.$arStatus['ID'].']&nbsp;'.$arStatus["NAME"],
			'ACTION' => $lAdmin->ActionDoGroup($f_ID, 'setstatus_'.$arStatus['ID'])
		);
	}
	$arActions[] = array(
		'SEPARATOR'=>true,
	);
	$arActions[] = array(
		'ICON'=>'delete',
		'TEXT'=>GetMessage('OBX_ORDER_LIST_DEL'),
		'ACTION'=>'if(confirm(\''.GetMessage('OBX_ORDER_LIST_DEL_CONF').'\')) '.$lAdmin->ActionDoGroup($f_ID, 'delete'),
	);
	$row->AddActions($arActions);
}

// Футтер
$lAdmin->AddFooter(
	array(
		array('title'=>GetMessage('MAIN_ADMIN_LIST_SELECTED'), 'value'=>$rsData->SelectedRowsCount()),
		array('counter'=>true, 'title'=>GetMessage('MAIN_ADMIN_LIST_CHECKED'), 'value'=>'0'),
	)
);
// Груповые операции
$lAdmin->AddGroupActionTable(array(
	'delete' => GetMessage('MAIN_ADMIN_LIST_DELETE'),
));

// Главное меню
$aContext = array(
	array(
		'TEXT'=>GetMessage('OBX_MARKET_ORDERS_LIST_ADD'),
		'LINK'=>'obx_market_order_edit.php?lang='.LANG,
		'TITLE'=>GetMessage('OBX_MARKET_ORDERS_LIST_ADD_TITLE'),
		'ICON'=>'btn_new',
	),
);
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();



// Заголовок
$APPLICATION->SetTitle(GetMessage('OBX_MARKET_ORDERS_LIST_TITLE'));
require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$lAdmin->DisplayList();
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
?>