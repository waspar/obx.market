<?php
/*******************************************
 ** @product OBX:Market Bitrix Module     **
 ** @authors                              **
 **         Maksim S. Makarov aka pr0n1x  **
 **         Morozov P. Artem aka tashiro  **
 ** @license Affero GPLv3                 **
 ** @mailto rootfavell@gmail.com          **
 ** @mailto tashiro@yandex.ru             **
 ** @copyright 2013 DevTop                **
 *******************************************/

namespace OBX\Sms\Settings;

IncludeModuleLangFile(__FILE__);

use OBX\Core\Settings\ATab as SettingsTab;
use OBX\Sms\Provider\Provider;

class ModuleSettingsMainTab extends SettingsTab {
	public $curProvider;

	public function showTabContent() {
		$arProvidersList = Provider::getProvidersList();
		$curProviderString = Provider::getCurrent()->PROVIDER_ID();
		if (!strlen($curProviderString) > 0) {
			$curProviderString = 'EMAIL';
		}
		$curProvider = $arProvidersList[$curProviderString];
		?>
	<tr class="heading">
		<td valign="top" colspan="2" align="center"><b><?=GetMessage('OBX_SMS_SETT_PROVIDER_TITLE')?></b></td>
	</tr>
	<tr>
		<td><?=GetMessage('OBX_SMS_SETT_PROVIDER')?></td>
		<td width="65%">
			<select name="PROVIDER_SELECTED" id="provider_list">
				<?foreach ($arProvidersList as $Provider): ?>
				<option <?if ($Provider->PROVIDER_ID() == $curProviderString): ?>selected<? endif;?>
						value="<?=$Provider->PROVIDER_ID()?>"><?=$Provider->PROVIDER_NAME() . " (" . $Provider->PROVIDER_ID() . ")"?></option>
				<? endforeach;?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<?
			foreach ($arProvidersList as $Provider):
				/**
				 * @var Provider $Provider
				 */
				?>
				<table cellpadding="0" cellspacing="0" border="0" class="edit-table provider_settings"
					   id="obx_sms_settings_provider_<?=$Provider->PROVIDER_ID()?>"
					   style="display: <?if ($Provider->PROVIDER_ID() == $curProvider->PROVIDER_ID()): ?>table<? else: ?>none<?endif;?>">
					<tr>
						<td></td>
						<td width="65%">
							<small>
								<?=$Provider->PROVIDER_DESCRIPTION()?>
							</small>
						</td>
					</tr>
					<?
					$curSettings = $Provider->getSettings(true);
					if (is_array($curSettings) && count($curSettings) > 0):?>
						<? foreach ($curSettings as $optionCode => $arOption): ?>
							<tr>
								<td class="field-name">
									<?=$arOption['NAME']?>
								</td>
								<td width="65%">
									<?=$Provider->getSettings()->getOptionInput($optionCode);?>
								</td>
							</tr>
							<? endforeach; ?>
						<? endif; ?>
				</table>
				<? endforeach; ?>
		</td>
	</tr>
	</td>
	<?
	}

	public function showTabScripts() {
		?>
	<script type="text/javascript">
		if (typeof(jQuery) == 'undefined') jQuery = false;
		(function ($) {
			$select = $("#provider_list");
			$allSettings = $("#obx_sms_settings_base_edit_table .provider_settings");
			$select.on("change", function () {
				$allSettings.hide();
				$curProv = $allSettings.closest("#obx_sms_settings_provider_" + $(this).val());
				$curProv.show();
			})
		})(jQuery)
	</script>
	<?
	}

	public function saveTabData() {
		$arProvidersList = Provider::getProvidersList();
		foreach ($arProvidersList as $Provider) {
			/**
			 * @var Provider $Provider
			 */
			$Provider->saveSettingsRequestData();
		}
		Provider::setCurrent($_REQUEST['PROVIDER_SELECTED']);
	}

}
