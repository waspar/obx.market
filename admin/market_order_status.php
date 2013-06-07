<?
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


$tableID = 'tbl_obx_order_status';
$OrderStatusDBS = OrderStatusDBS::getInstance();
$oSort = new CAdminSorting($tableID, 'SORT', 'ASC');
$lAdmin = new CAdminList($tableID, $oSort);

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
		if( count($arUpdateFields)>0 ) {
			if( !$OrderStatusDBS->update($arUpdateFields) ) {
				$lAdmin->AddUpdateError($OrderStatusDBS->popLastError(), $ID);
			}
		}
	}

}
if( ($arID = $lAdmin->GroupAction()) ) {
	if(false && $_REQUEST['action_target']=='selected'){
		$rsData = $OrderStatusDBS->getList(array($by=>$objOrder), $arFilter);
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
				if (!$OrderStatusDBS->delete($ID)){
					$DB->Rollback();
					$arError = $OrderStatusDBS->popLastError('ARRAY');
					$lAdmin->AddGroupError($arError['TEXT'], $ID);
				}
				$DB->Commit();
				break;
		}
	}
}


/**
 * Выборка
 */
$rsData = $OrderStatusDBS->getList(array($by=>$order),$arFilter);
$rsData = new CAdminResult($rsData, $tableID);

$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage('OBX_MARKET_ORDER_STATUS_LIST_NAV')));

// Заголовки
$aHeaders = array(
	array('id'=>'ID', 'content'=>'ID', 'sort'=>'ID', 'default'=>true),
	array('id'=>'CODE', 'content'=>GetMessage('OBX_ORDER_STATUS_F_CODE'), 'sort'=>'CODE', 'default'=>true),
	array('id'=>'NAME', 'content'=>GetMessage('OBX_ORDER_STATUS_F_NAME'), 'sort'=>'NAME', 'default'=>true),
	array('id'=>'SORT', 'content'=> GetMessage('OBX_ORDER_STATUS_F_SORT'), 'sort'=>'SORT', 'default'=>true),
	array('id'=>'COLOR', 'content'=> GetMessage('OBX_ORDER_STATUS_F_COLOR'), 'default'=>true),
	array('id'=>'DESCRIPTION', 'content'=>GetMessage('OBX_ORDER_STATUS_F_DESCRIPTION'), 'sort'=>'DESCRIPTION', 'default'=>true),
	array('id'=>'IS_SYS', 'content'=>GetMessage('OBX_ORDER_STATUS_F_IS_SYS'), 'sort'=>'IS_SYS', 'default'=>false),
);
$lAdmin->AddHeaders($aHeaders);
// Обработка строк
while( $arRes = $rsData->NavNext(true, 'f_') ) {
	$row =& $lAdmin->AddRow($f_ID, $arRes);
	
	$row->AddViewField('NAME',$f_NAME);
	$row->AddInputField('NAME', array('size'=>20));
	//$row->AddEditField("NAME", '<input name="FIELDS[NAME]" value="'.$arRes['NAME'].'" />');

	$row->AddViewField('CODE',$f_CODE);
	$row->AddInputField('CODE', array('size'=>20));
	$row->AddInputField('CODE', array('size'=>20));
	//$row->AddEditField("CODE", '<input name="FIELDS[CODE]" value="'.$arRes['CODE'].'" />');

	$row->AddViewField('DESCRIPTION',$f_DESCRIPTION);
	$row->AddInputField('DESCRIPTION', array('size'=>20));
	//$row->AddEditField("DESCRIPTION", '<input name="FIELDS[DESCRIPTION]" value="'.$arRes['DESCRIPTION'].'" />');

	$row->AddViewField('SORT',$f_SORT);
	$row->AddInputField('SORT', array('size'=>4));
	
	$row->AddViewField('COLOR',$f_COLOR);
	$row->AddInputField('COLOR', array('size'=>7));

	$row->AddViewField('IS_SYS', ($f_IS_SYS=='Y')?GetMessage('YES'):GetMessage('NO'));
	
	// Меню строки
	$arActions = Array();
	$arActions[] = array(
		'ICON' => 'obx-order-list-edit',
		'TEXT' => GetMessage('OBX_ORDER_STATUS_LIST_EDIT'),
		'ACTION' => $lAdmin->ActionRedirect('obx_market_order_status_edit.php?ID='.$f_ID),
		'DEFAULT' => 'Y',
	);
	$arActions[] = array(
		'SEPARATOR'=>true,
	);
	$arActions[] = array(
		'ICON'=>'delete',
		'TEXT'=>GetMessage('OBX_ORDER_STATUS_LIST_DEL'),
		'ACTION'=>'if(confirm(\''.GetMessage('OBX_STATUS_LIST_DEL_CONF').'\')) '.$lAdmin->ActionDoGroup($f_ID, 'delete'),
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
		'TEXT'=>GetMessage('OBX_MARKET_ORDER_STATUS_LIST_ADD'),
		'LINK'=>'obx_market_order_status_edit.php?lang='.LANG,
		'TITLE'=>GetMessage('OBX_MARKET_ORDER_STATUS_LIST_ADD_TITLE'),
		'ICON'=>'btn_new',
	),
);
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();



// Заголовок
$APPLICATION->SetTitle(GetMessage('OBX_MARKET_ORDER_STATUS_LIST_TITLE'));
require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$lAdmin->DisplayList();
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
?>