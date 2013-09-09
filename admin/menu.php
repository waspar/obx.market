<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

include_once $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.market/classes/BXMainEventsHandlers.php';
$aMenu = OBX_Market_BXMainEventsHandlers::getGlobalMenuItems();
return $aMenu;
