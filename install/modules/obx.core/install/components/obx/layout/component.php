<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if( !CModule::IncludeModule('obx.core') ) {
	ShowError(GetMessage('OBX_CORE_IS_NOT_INSTALLED'));
	return;
}

$this->IncludeComponentTemplate();
?>