<?
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


$tableID = 'tbl_obx_order_property';
$OrderPropertyDBS = OBX_OrderPropertyDBS::getInstance();
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
			if( !$OrderPropertyDBS->update($arUpdateFields) ) {
				$lAdmin->AddUpdateError($OrderPropertyDBS->popLastError(), $ID);
			}
		}
	}

}
if( ($arID = $lAdmin->GroupAction()) ) {
	if(false && $_REQUEST['action_target']=='selected'){
		$rsData = $OrderPropertyDBS->getList(array($by=>$objOrder), $arFilter);
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
				if (!$OrderPropertyDBS->delete($ID)){
					$DB->Rollback();
					$lAdmin->AddGroupError($OrderPropertyDBS->popLastError(), $ID);
				}
				$DB->Commit();
				break;
		}
	}
}


/**
 * Выборка
 */
$rsData = $OrderPropertyDBS->getList(array($by=>$order),$arFilter);
$rsData = new CAdminResult($rsData, $tableID);

$rsData->NavStart();
$lAdmin->NavText($rsData->GetNavPrint(GetMessage('OBX_MARKET_ORDER_PROPS_LIST_NAV')));

// Заголовки
$aHeaders = array(
	array('id'=>'ID', 'content'=>'ID', 'sort'=>'ID', 'default'=>true),
	array('id'=>'CODE', 'content'=>GetMessage('OBX_ORDER_PROP_F_CODE'), 'sort'=>'CODE', 'default'=>false),
	array('id'=>'SORT', 'content'=> GetMessage('OBX_ORDER_PROP_F_SORT'), 'sort'=>'SORT', 'default'=>true),
	array('id'=>'PROPERTY_TYPE', 'content'=> GetMessage('OBX_ORDER_PROP_F_PROPERTY_TYPE'), 'sort'=>'PROPERTY_TYPE', 'default'=>true),
	array('id'=>'NAME', 'content'=>GetMessage('OBX_ORDER_PROP_F_NAME'), 'sort'=>'NAME', 'default'=>true),
	array('id'=>'DESCRIPTION', 'content'=>GetMessage('OBX_ORDER_PROP_F_DESCRIPTION'), 'sort'=>'DESCRIPTION', 'default'=>true),

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

	switch($f_PROPERTY_TYPE) {
		case 'S':
			$f_PROPERTY_TYPE = '[S] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_S');
			break;
		case 'N':
			$f_PROPERTY_TYPE = '[N] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_N');
			break;
		case 'T':
			$f_PROPERTY_TYPE = '[T] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_T');
			break;
		case 'L':
			$f_PROPERTY_TYPE = '[L] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_L');
			break;
		case 'C':
			$f_PROPERTY_TYPE = '[C] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_C');
			break;
	}
	$row->AddViewField('PROPERTY_TYPE',$f_PROPERTY_TYPE);
	$row->AddSelectField('PROPERTY_TYPE', array(
		'S' => '[S] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_S'),
		'N' => '[N] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_N'),
		'T' => '[T] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_T'),
		'L' => '[L] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_L'),
		'C' => '[C] '.GetMessage('OBX_MARKET_FLD_EDIT_PROP_TYPE_C')
	));

	$row->AddViewField('DESCRIPTION',$f_DESCRIPTION);
	$row->AddInputField('DESCRIPTION', array('size'=>20));
	//$row->AddEditField("DESCRIPTION", '<input name="FIELDS[DESCRIPTION]" value="'.$arRes['DESCRIPTION'].'" />');

	$row->AddViewField('SORT',$f_SORT);
	$row->AddInputField('SORT', array('size'=>20));
	
	// Меню строки
	$arActions = Array();
	$arActions[] = array(
		'ICON' => 'obx-order-list-edit',
		'TEXT' => GetMessage('OBX_ORDER_PROP_LIST_EDIT'),
		'ACTION' => $lAdmin->ActionRedirect('obx_market_order_props_edit.php?ID='.$f_ID),
		'DEFAULT' => 'Y',
	);
	$arActions[] = array(
		'SEPARATOR'=>true,
	);
	$arActions[] = array(
		'ICON'=>'delete',
		'TEXT'=>GetMessage('OBX_ORDER_PROP_LIST_DEL'),
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
		'TEXT'=>GetMessage('OBX_MARKET_ORDER_PROPS_LIST_ADD'),
		'LINK'=>'obx_market_order_props_edit.php?lang='.LANG,
		'TITLE'=>GetMessage('OBX_MARKET_ORDER_PROPS_LIST_ADD_TITLE'),
		'ICON'=>'btn_new',
	),
);
$lAdmin->AddAdminContextMenu($aContext);
$lAdmin->CheckListMode();



// Заголовок
$APPLICATION->SetTitle(GetMessage('OBX_MARKET_ORDER_PROPS_LIST_TITLE'));
require_once ($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$lAdmin->DisplayList();
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
?>