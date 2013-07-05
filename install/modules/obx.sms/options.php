<?php
/***********************************************
 ** @product OBX:SMS Bitrix Module            **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 **         Morozov P. Artem aka tashiro      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

use OBX\Sms\Settings\ModuleSettingsMainTab;

IncludeModuleLangFile(__FILE__);

if (!$USER->IsAdmin()) return;
if (!CModule::IncludeModule("obx.sms")) return;

/**
 * Закладки
 */
$arTabsList = array(
	array(
		"DIV" => "obx_sms_settings_base",
		"TAB" => GetMessage("OBX_SMS_SETTINGS_TAB_BASE"),
		"ICON" => "settings_currency",
		"TITLE" => GetMessage("OBX_SMS_SETTINGS_TITLE_BASE"),
		"CONTROLLER" => ModuleSettingsMainTab::GetTabController()
	)
);
$TabControl = new CAdminTabControl("tabSettings", $arTabsList);
/*
 * Обработаем пост
 */
if ($REQUEST_METHOD == "POST" && strlen($Update . $Apply) > 0 && check_bitrix_sessid()) {
	foreach ($arTabsList as &$arTab) {
		/**
		 * @var \OBX\Core\Settings\ATab $arTabCtrl
		 */
		$arTabCtrl = &$arTab['CONTROLLER'];
		$arTabCtrl->saveTabData();
		if (strlen($Update) > 0 && strlen($_REQUEST["back_url_settings"]) > 0) {
			LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else {
			LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $TabControl->ActiveTabParam());
		}
	}
}

/**
 * Шаблоны
 */
$APPLICATION->AddHeadScript("/bitrix/modules/obx.sms/js/jquery-1.8.2.min.js");
?>

<div id="obx_sms_settings">
	<form method="post"
		  action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">

		<?
		$TabControl->Begin();
		foreach ($arTabsList as &$arTab) {
			$TabControl->BeginNextTab();
			if (!empty($arTab["CONTROLLER"])) {
				/**
				 * @var \OBX\Core\Settings\ATab $arTabCtrl
				 */
				$arTabCtrl = &$arTab['CONTROLLER'];
				$arTabCtrl->showMessages();
				$arTabCtrl->showErrors();
				$arTabCtrl->showTabContent();
			}
		}
		?>
		<?$TabControl->Buttons();?>
		<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>"
			   title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
		<input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>"
			   title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
		<?if (strlen($_REQUEST["back_url_settings"]) > 0): ?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>"
			   title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>"
			   onclick="window.location='<?echo htmlspecialchars(\CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
		<? endif?>
		<?=bitrix_sessid_post();?>
		<?
		$TabControl->End();
		?>
	</form>
</div>
<?
foreach ($arTabsList as &$arTab) {
	if (!empty($arTab["CONTROLLER"])) {
		/**
		 * @var \OBX\Sms\Settings\Tab $arTabCtrl
		 */
		$arTabCtrl = &$arTab['CONTROLLER'];
		?>
	<div id="<?=$arTab["DIV"] . "_scripts"?>"><?
		$arTabCtrl->showTabScripts();
		?></div><?
	}
}
?>