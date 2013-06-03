<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 **         Artem P. Morozov  aka tashiro     **
 ** @License GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/



namespace OBX\Market;

use OBX\Core\Tools;
use OBX\Core\CMessagePool;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\CMessagePoolDecorator;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;
use OBX\Core\DBSResult;

use OBX\Market\Currency;
use OBX\Market\Currency as OBX_Currency	;
use OBX\Market\CurrencyDBS;
use OBX\Market\CurrencyDBS as OBX_CurrencyDBS;
use OBX\Market\CurrencyFormat;
use OBX\Market\CurrencyFormat as OBX_CurrencyFormat;
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

IncludeModuleLangFile(__FILE__);

abstract class Settings extends CMessagePoolDecorator {

	final protected function __construct() {
	}

	final protected function __clone() {
	}

	static protected $_arInstances = array();
	static protected $_arLangList = null;

	/**
	 * @param String $tabCode Постфикс имени класса
	 * @return Settings
	 */
	final static public function getController($tabCode) {
		if (!preg_match('~^[a-zA-Z\_][a-zA-Z0-9\_]*$~', $tabCode)) {
			return null;
		}
		if (!class_exists('OBX\Market\Settings_' . $tabCode)) {
			return null;
		}

		if (empty(self::$_arInstances[$tabCode])) {
			$className = 'OBX\Market\Settings_' . $tabCode;
			$TabContentObject = new $className;
			if ($TabContentObject instanceof self) {
				self::$_arInstances[$tabCode] = $TabContentObject;
			}
		}
		return self::$_arInstances[$tabCode];
	}


	/**
	 * @return Array
	 */
	static public function getLangList() {
		if (self::$_arLangList == null) {
			$rsLang = \CLanguage::GetList($by = "sort", $sort = "asc", $arLangFilter = array("ACTIVE" => "Y"));
			$arLangList = array();
			while ($arLang = $rsLang->Fetch()) {
				$arLangList[$arLang["ID"]] = $arLang;
			}
			if (!empty($arLangList)) {
				self::$_arLangList = $arLangList;
			}
		}
		return self::$_arLangList;
	}


	protected $listTableColumns = 1;

	public function showMessages($colspan = -1) {
		$colspan == intval($colspan);
		if ($colspan < 0) {
			$colspan = $this->listTableColumns;
		}
		$arMessagesList = $this->getMessages();
		if (count($arMessagesList) > 0) {
			?>
		<tr>
			<td<?if ($colspan > 1): ?> colspan="<?=$colspan?>"<? endif?>><?
				foreach ($arMessagesList as $arMessage) {
					ShowNote($arMessage["TEXT"]);
				}
				?></td>
		</tr><?
		}
	}

	public function showWarnings($colspan = -1) {
		$colspan == intval($colspan);
		if ($colspan < 0) {
			$colspan = $this->listTableColumns;
		}
		$arWarningsList = $this->getWarnings();
		if (count($arWarningsList) > 0) {
			?>
		<tr>
			<td<?if ($colspan > 1): ?> colspan="<?=$colspan?>"<? endif?>><?
				foreach ($arWarningsList as $arWarning) {
					ShowNote($arWarning["TEXT"]);
				}
				?></td>
		</tr><?
		}
	}

	public function showErrors($colspan = -1) {
		$colspan == intval($colspan);
		if ($colspan < 0) {
			$colspan = $this->listTableColumns;
		}
		$arErrorsList = $this->getErrors();
		if (count($arErrorsList) > 0) {
			?>
		<tr>
			<td<?if ($colspan > 1): ?> colspan="<?=$colspan?>"<? endif?>><?
				foreach ($arErrorsList as $arError) {
					ShowError($arError["TEXT"]);
				}
				?></td>
		</tr><?
		}
	}

	abstract public function showTabContent();

	abstract public function showTabScripts();

	abstract public function saveTabData();
}

class Settings_Currency extends Settings {
	protected $listTableColumns = 10;

	public function showTabContent() {
		$arCurrencyList = OBX_Currency::getListArray();
		$arCurrencyFormatList = OBX_CurrencyFormat::getListGroupedByLang(array(
			"CURRENCY_SORT" => "ASC",
			"CURRENCY" => "ASC",
			"LANGUAGE_SORT" => "ASC"
		));
		$arLangList = self::getLangList();
		$countLangList = count($arLangList);
		?>
	<tr>
		<td>
			<table class="internal" style="width:100%">
	<tr class="heading">
		<td class="field-name"></td>
		<td><span class="require">*</span>&nbsp;<?=GetMessage("OBX_SETT_CURRENCY_F_CODE")?>&nbsp;<a href="#"
																									class="help-inline">?</a>
		</td>
		<Td><?=GetMessage("OBX_SETT_CURRENCY_F_SORT")?></Td>
		<td><?=GetMessage("OBX_SETT_CURRENCY_F_IS_DEFAULT")?>&nbsp;<a href="#" class="help-inline">?</a></td>
		<td><?=GetMessage("OBX_SETT_CURRENCY_F_LANG")?></td>
		<td><span class="require">*</span>&nbsp;<?=GetMessage("OBX_SETT_CURRENCY_F_NAME")?></td>
		<td><span class="require">*</span>&nbsp;<?=GetMessage("OBX_SETT_CURRENCY_F_FORMAT")?>&nbsp;<a href="#"
																									  class="help-inline">?</a>
		</td>
		<td><?=GetMessage("OBX_SETT_CURRENCY_F_THOUS_SEP")?>&nbsp;<a href="#" class="help-inline">?</a></td>
		<td><?=GetMessage("OBX_SETT_CURRENCY_F_DEC_POINT")?>&nbsp;<a href="#" class="help-inline">?</a></td>
		<td><?=GetMessage("OBX_SETT_CURRENCY_F_PRECISION")?>&nbsp;<a href="#" class="help-inline">?</a></td>
		<td><?=GetMessage("OBX_SETT_CURRENCY_BTN_DELETE")?></label></td>
	</tr>
	<? if (count($arCurrencyFormatList) > 0): ?>
			<? foreach ($arCurrencyFormatList as $currency => $arCurrency): ?>
			<tr>
				<td class="field-name"></td>
				<td rowspan="<?=$countLangList?>" class="currency-code center">
					<?=$currency?>
				</td>
				<td rowspan="<?=$countLangList?>" class="center">
					<input type="text" name="obx_currency_update[<?=$currency?>][sort]" size="4"
						   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_SORT")?>" value="<?=$arCurrency["SORT"]?>"/>
				</td>
				<td rowspan="<?=($countLangList)?>" class="center"><input type="radio" name="obx_currency_default"
														   value="<?=$currency?>"<?if ($arCurrency["IS_DEFAULT"] == "Y"): ?>
														   checked="checked"<? endif?> /></td>
				<?$iLang = 0;
				foreach ($arCurrency["LANG"] as $languageID => &$arFormat):
					$iLang++;
					?>
					<? if ($iLang > 1): ?>
				<tr>
					<td class="field-name"></td>
					<? endif ?>
					<td><?=$arFormat["LANGUAGE_NAME"]?></td>
					<td>
						<input type="text" name="obx_currency_update[<?=$currency?>][<?=$languageID?>][name]"
							   value="<?=$arFormat["NAME"]?>"
							   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_NAME")?>"/>
					</td>
					<td>
						<input type="text" name="obx_currency_update[<?=$currency?>][<?=$languageID?>][format]"
							   value="<?=$arFormat["FORMAT"]?>"
							   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_FORMAT")?>"/>
					</td>
					<td>
						<input type="text" class="thous_sep"
							   name="obx_currency_update[<?=$currency?>][<?=$languageID?>][thousand_sep]"
							   value="<?=$arFormat["THOUSANDS_SEP"]?>"/>
						<label>
							<input type="checkbox" class="thous_sep_space"
								   name="obx_currency_update[<?=$currency?>][<?=$languageID?>][thousands_sep_space]"<?if ($arFormat["THOUSANDS_SEP"] == " "): ?>
								   checked="checked"<? endif?> />
							<?=GetMessage("OBX_SETT_SPACE")?>
						</label>
					</td>
					<td>
						<input type="text" class="dec_point"
							   name="obx_currency_update[<?=$currency?>][<?=$languageID?>][dec_point]"
							   value="<?=$arFormat["DEC_POINT"]?>"/>
					</td>
					<td>
						<select name="obx_currency_update[<?=$currency?>][<?=$languageID?>][dec_precision]">
							<?for ($precision = 0; $precision <= 5; $precision++): ?>
							<option value="<?=$precision?>"<?if ($precision == $arFormat["DEC_PRECISION"]): ?>
									selected<? endif?>><?=$precision?></option>
							<? endfor?>
						</select>
					</td>
					<? if ($iLang == 1): ?>
					<td rowspan="<?=$countLangList?>" class="remove_currency_col center">
						<input type="checkbox" name="obx_currency_delete[<?=$currency?>]" value="<?=$currency?>"/>
					</td>
					<? endif ?>
					<? if ($iLang < $countLangList): ?>
				</tr>
				<? endif ?>
					<? endforeach ?>
				</tr>
				<? endforeach ?>
		<? endif; ?>

		<tr class="replace">
			<td colspan="<?=$this->listTableColumns?>"></td>
		</tr>
		<tr>
			<td class="field-name"></td>
			<td colspan="10"><input class="add_new_item" type="button"
								   value="<?=GetMessage("OBX_SETT_CURRENCY_BTN_ADD_ITEM")?>"/></td>
		</tr>
		<tr>
			<td colspan="<?=$this->listTableColumns?>">
				<input type="button" id="obx_currency_btn_save" value="<?=GetMessage("OBX_SETT_CURRENCY_BTN_SAVE")?>"/>
				<input type="button" id="obx_currency_btn_cancel"
					   value="<?=GetMessage("OBX_SETT_CURRENCY_BTN_CANCEL")?>"/>
			</td>
		</tr>
		<tr>
			<td class="delimiter hard" colspan="<?=$this->listTableColumns?>"></td>
		</tr>

			</table>
		</td>
	</tr><?
	}

	public function showTabScripts() {
		$arLangList = self::getLangList();
		$countLangList = count($arLangList);?>
		<?=
		'<script type="text/x-custom-template" id="obx_market_currency_row_tmpl">'
		; ?>
		<tr data-new-row="$index">
			<td rowspan="<?=($countLangList + 1)?>" class="field-name"></td>
			<td rowspan="<?=($countLangList + 1)?>" class="remove_new_item center">
				<input type="text" name="obx_currency_new[$index][currency]" size="3"
					   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_CODE_")?>" value="$currency"/>
			</td>
			<td rowspan="<?=$countLangList + 1?>" class="center">
				<input type="text" name="obx_currency_new[$index][sort]" size="4"
					   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_SORT")?>" value="$sort"/>
			</td>
			<td rowspan="<?=($countLangList + 1)?>" class="center"><input type="radio" name="obx_currency_default" value="new_$index"/>
			</td>
		</tr>
		<?$iLang = 0;
		foreach ($arLangList as $arLang): ?>
			<?$iLang++;?>
			<tr data-new-row="$index">
				<td><?=$arLang["NAME"]?></td>
				<td>
					<input type="text" name="obx_currency_new[$index][<?=$arLang["ID"]?>][name]"
						   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_NAME")?>" value="$name"/>
				</td>
				<td>
					<input type="text" name="obx_currency_new[$index][<?=$arLang["ID"]?>][format]"
						   placeholder="<?=GetMessage("OBX_SETT_CURRENCY_F_FORMAT")?>" value="$format"/>
				</td>
				<td>
					<input type="text" class="thous_sep"
						   name="obx_currency_new[$index][<?=$arLang["ID"]?>][thousand_sep]" value="$thousSep"/>
					<label>
						<input type="checkbox" class="thous_sep_space"
							   name="obx_currency_new[$index][<?=$arLang["ID"]?>][thousands_sep_space]" $thousSpaceSepChecked />
						<?=GetMessage("OBX_SETT_SPACE")?>
					</label>
				</td>
				<td>
					<input type="text" class="dec_point"
						   name="obx_currency_new[$index][<?=$arLang["ID"]?>][dec_point]"
						   value="$decPoint"/>
				</td>
				<td>
					<select name="obx_currency_new[$index][<?=$arLang["ID"]?>][dec_precision]">
						<?for ($precision = 0; $precision <= 5; $precision++): ?>
						<option value="<?=$precision?>" $decimalPrecisionSelected_<?=$precision?>><?=$precision?></option>
						<? endfor?>
					</select>
				</td>
				<?if($iLang ==1):?>
				<td rowspan="<?=count($arLangList)?>" class="remove_new_item center">
					<input data-new-row="$index" type="button" value="<?=GetMessage("OBX_SETT_PRICE_BTN_DELETE")?>"/>
				</td>
				<?endif?>
			</tr>
			<? endforeach ?>
		<tr class="replace">
			<td colspan="<?=$this->listTableColumns?>">
			</td>
		</tr>
		<?= '</script>'?>
		<?
	}

	public function saveTabData() {
		$this->updateCurrencyList();
		$this->deleteCurrencyList();
		$this->addNewCurrencyList();
	}

	protected function deleteCurrencyList() {
		if (!is_array($_REQUEST["obx_currency_delete"])) {
			return false;
		}
		$arCurrencyDeleteID = $_REQUEST["obx_currency_delete"];
		$arDeleteSuccessList = array();
		foreach ($arCurrencyDeleteID as $currency => $delText) {
			$bDelFormatSuccess = OBX_CurrencyFormat::deleteByFilter(array("CURRENCY" => $currency));
			$bDelCurrencySuccess = OBX_Currency::delete($currency);
			if (!$bDelFormatSuccess) {
				$this->addError(OBX_CurrencyFormat::popLastError());
			}
			if (!$bDelCurrencySuccess) {
				$this->addError(OBX_Currency::popLastError());
			}
			if ($bDelFormatSuccess && $bDelCurrencySuccess) {
				if (!in_array($currency, $arDeleteSuccessList)) {
					$arDeleteSuccessList[] = $currency;
				}
			}
		}
		if (count($arDeleteSuccessList) > 0) {
			$this->addMessage(GetMessage("OBX_SETT_CURRENCY_MESSAGE_3", array(
				"#CURRENCY_LIST#" => implode(', ', $arDeleteSuccessList)
			)), 3);
			return true;
		}
		return false;
	}

	protected function updateCurrencyList() {
		if (!is_array($_REQUEST["obx_currency_update"])) {
			return false;
		}
		$arCurrencyDeleteID = $_REQUEST["obx_currency_delete"];
		$arCurrencyUpdateID = $_REQUEST["obx_currency_update"];

		$arLangList = self::getLangList();
		//$countLangList = count($arLangList);

		$arUpdateSuccessCurrencyList = array();

		foreach ($arCurrencyUpdateID as $currecny => &$arCurrencyDataRaw) {
			$arCurrencyExistsList = OBX_Currency::getListArray($currecny);
			if (empty($arCurrencyExistsList)) {
				$this->addError(GetMessage("OBX_SETT_CURRENCY_ERROR_2", array("#CURRENCY#" => htmlspecialcharsEx($currecny))));
				continue;
			}
			$arExistCurrency = $arCurrencyExistsList[0];
			if (!empty($arCurrencyDataRaw) && empty($arCurrencyDeleteID[$currecny])) {
				$arUpdateCurrencyFields = array("CURRENCY" => $currecny);
				$bCurrencyUpdateSuccess = false;
				if (isset($arCurrencyDataRaw["sort"]) && $arCurrencyDataRaw["sort"] != $arExistCurrency["SORT"]) {
					$arUpdateCurrencyFields["SORT"] = intval($arCurrencyDataRaw["sort"]);
				}
				if (isset($arCurrencyDataRaw["rate"]) && $arCurrencyDataRaw["rate"] != $arExistCurrency["RATE"]) {
					$arUpdateCurrencyFields["RATE"] = intval($arCurrencyDataRaw["rate"]);
				}
				if (isset($arCurrencyDataRaw["cource"]) && $arCurrencyDataRaw["cource"] != $arExistCurrency["COURSE"]) {
					$arUpdateCurrencyFields["COURSE"] = intval($arCurrencyDataRaw["cource"]);
				}

				if (count($arUpdateCurrencyFields) > 1) {
					$bCurrencyUpdateSuccess = OBX_Currency::update($arUpdateCurrencyFields);
					if (!$bCurrencyUpdateSuccess) {
						$arCurrencyLastError = OBX_Currency::popLastError('ARRAY');
						$this->addError($arCurrencyLastError["TEXT"], $arCurrencyLastError["CODE"]);
					} else {
						if (!in_array($currecny, $arUpdateSuccessCurrencyList)) {
							$arUpdateSuccessCurrencyList[] = $currecny;
						}
					}
				}

				foreach ($arLangList as &$arLang) {
					if (!array_key_exists($arLang["LID"], $arCurrencyDataRaw)) {
						continue;
					}
					$bFormatUpdateSuccess = false;
					$arExistsFormatList = OBX_CurrencyFormat::getListArray(null, array(
						"CURRENCY" => $currecny,
						"LANGUAGE_ID" => $arLang["LID"]
					));
					if (empty($arExistsFormatList)) {
						continue;
					}
					$arExistsFormat = $arExistsFormatList[0];
					$arFormatRaw = $arCurrencyDataRaw[$arLang["LID"]];
					$arUpdateFormatFields = array("ID" => $arExistsFormat["ID"]);
					if (isset($arFormatRaw["name"]) && $arFormatRaw["name"] != $arExistsFormat["NAME"]) {
						$arUpdateFormatFields["NAME"] = trim($arFormatRaw["name"]);
					}
					if (isset($arFormatRaw["format"]) && $arFormatRaw["format"] != $arExistsFormat["FORMAT"]) {
						$arUpdateFormatFields["FORMAT"] = $arFormatRaw["format"];
					}
					if (isset($arFormatRaw["thousand_sep"]) && $arFormatRaw["thousand_sep"] != $arExistsFormat["THOUSANDS_SEP"]) {
						$arUpdateFormatFields["THOUSANDS_SEP"] = trim($arFormatRaw["thousand_sep"]);
					}
					if (isset($arFormatRaw["dec_precision"]) && $arFormatRaw["dec_precision"] != $arExistsFormat["DEC_PRECISION"]) {
						$arUpdateFormatFields["DEC_PRECISION"] = intval($arFormatRaw["dec_precision"]);
					}
					if (isset($arFormatRaw["dec_point"]) && $arFormatRaw["dec_point"] != $arExistsFormat["DEC_POINT"]) {
						$arUpdateFormatFields["DEC_POINT"] = trim($arFormatRaw["dec_point"]);
					}
					if (count($arUpdateFormatFields) > 1) {
						if (empty($arUpdateFormatFields["ID"])) {
							$arNewCurrencyFormatOnUpdate = $arUpdateFormatFields;
							$arNewCurrencyFormatOnUpdate["CURRENCY"] = $currecny;
							$arNewCurrencyFormatOnUpdate["LANGUAGE_ID"] = $arLang["LID"];
							if (!isset($arUpdateFormatFields["NAME"])) {
								$arNewCurrencyFormatOnUpdate["NAME"] = '';
							}
							if (!isset($arUpdateFormatFields["FORMAT"])) {
								$arNewCurrencyFormatOnUpdate["FORMAT"] = '#';
							}
							if (!isset($arUpdateFormatFields["THOUSANDS_SEP"])) {
								$arNewCurrencyFormatOnUpdate["THOUSANDS_SEP"] = " ";
							}
							if (!isset($arUpdateFormatFields["DEC_PRECISION"])) {
								$arNewCurrencyFormatOnUpdate["DEC_PRECISION"] = 2;
							}
							if (!isset($arUpdateFormatFields["DEC_POINT"])) {
								$arNewCurrencyFormatOnUpdate["DEC_POINT"] = '.';
							}
							$bFormatAddOnCurrencyUpdateSuccess = OBX_CurrencyFormat::add($arNewCurrencyFormatOnUpdate);
							if (!$bFormatAddOnCurrencyUpdateSuccess) {
								$arAddOnUpdateError = OBX_CurrencyFormat::popLastError('ALL');
								$this->addError($arAddOnUpdateError["TEXT"], $arAddOnUpdateError["CODE"]);
							}
						} else {
							$bFormatUpdateSuccess = OBX_CurrencyFormat::update($arUpdateFormatFields);
							if (!$bFormatUpdateSuccess) {
								$arFormatLastError = OBX_CurrencyFormat::popLastError('ARRAY');
								$this->addError($arFormatLastError["TEXT"], $arFormatLastError["CODE"]);
							} else {
								if (!in_array($currecny, $arUpdateSuccessCurrencyList)) {
									$arUpdateSuccessCurrencyList[] = $currecny;
								}
							}
						}
					}
				}
			}
		}
		if (isset($_REQUEST["obx_currency_default"])) {
			if (strpos($_REQUEST["obx_currency_default"], 'new_') === false) {
				$defCurrency = substr($_REQUEST["obx_currency_default"], 0, 3);
				if (!array_key_exists($defCurrency, $arCurrencyDeleteID)) {
					$bIsAlreadyDefault = false;
					if (OBX_Currency::setDefault($defCurrency, $bIsAlreadyDefault)) {
						if (!$bIsAlreadyDefault) {
							if (!in_array($defCurrency, $arUpdateSuccessCurrencyList)) {
								$arUpdateSuccessCurrencyList[] = $defCurrency;
							}
						}
					}
				}
			}
		}
		if (count($arUpdateSuccessCurrencyList) > 0) {
			$this->addMessage(GetMessage("OBX_SETT_CURRENCY_MESSAGE_2", array(
				"#CURRENCY_LIST#" => implode(', ', $arUpdateSuccessCurrencyList)
			)), 2);
		}
	}

	protected function addNewCurrencyList() {
		if (!is_array($_REQUEST["obx_currency_new"])) {
			return false;
		}
		$arLangList = self::getLangList();
		$countLangList = count($arLangList);
		$arNewCurrenciesRaw = $_REQUEST["obx_currency_new"];
		$strNewSuccessIDList = '';

		$newDefaultIndex = false;
		if (isset($_REQUEST["obx_currency_default"])) {
			if (strpos($_REQUEST["obx_currency_default"], 'new_') !== false) {
				$newDefaultIndex = substr($_REQUEST["obx_currency_default"], 4);
				$newDefaultIndex = intval($newDefaultIndex);
			}
		}

		foreach ($arNewCurrenciesRaw as $newCurrencyIndex => &$arNewCurrencyRaw) {
			$newCurrencyIndex = intval($newCurrencyIndex);
			$arNewCurrency = array(
				"SORT" => intval($arNewCurrencyRaw["sort"]),
				"CURRENCY" => substr($arNewCurrencyRaw["currency"], 0, 3),
				"COURSE" => floatval($arNewCurrencyRaw["cource"]),
				"RATE" => floatval($arNewCurrencyRaw["rate"])
			);
			if (!preg_match('~^[a-zA-Z0-9]{1,3}$~', trim($arNewCurrency["CURRENCY"]))) {
				self::addError(GetMessage("OBX_SETT_CURRENCY_ERROR_1"), 1);
				continue;
			}
			$arNewCurrency["CURRENCY"] = trim($arNewCurrency["CURRENCY"]);
			$bAddCurrencySuccess = false;
			$countAllFormatsIsSuccess = 0;
			foreach ($arLangList as $langID => &$arLang) {
				if (!array_key_exists($arLang["LID"], $arNewCurrencyRaw)) {
					$this->addError(GetMessage("OBX_SETT_CURRENCY_ERROR_3"), 3);
					continue;
				}
				if (strlen(trim($arNewCurrencyRaw[$arLang["LID"]]["name"])) < 1) {
					$this->addError(GetMessage("OBX_SETT_CURRENCY_ERROR_4", array(
						"#CURRENCY#" => $arNewCurrency["CURRENCY"],
						"#LANG_NAME#" => $arLang["NAME"]
					)), 4);
					continue;
				}
				if (!$bAddCurrencySuccess) {
					$bAddCurrencySuccess = OBX_Currency::add($arNewCurrency);
					if (!$bAddCurrencySuccess) {
						$this->addError(OBX_Currency::popLastError());
						continue;
					}
				}

				$arNewCurrencyFormat = array(
					"CURRENCY" => $arNewCurrency["CURRENCY"],
					"LANGUAGE_ID" => $arLang["LID"],
					"NAME" => trim($arNewCurrencyRaw[$arLang["LID"]]["name"]),
					"FORMAT" => trim($arNewCurrencyRaw[$arLang["LID"]]["format"])
				);
				if (!empty($arNewCurrencyRaw[$arLang["LID"]]["thousand_sep"])) {
					$arNewCurrencyFormat["THOUSANDS_SEP"] = $arNewCurrencyRaw[$arLang["LID"]]["thousand_sep"];
				}
				if (!empty($arNewCurrencyRaw[$arLang["LID"]]["dec_precision"])) {
					$arNewCurrencyFormat["DEC_PRECISION"] = $arNewCurrencyRaw[$arLang["LID"]]["dec_precision"];
				}

				$newCurrencyFormatID = OBX_CurrencyFormat::add($arNewCurrencyFormat);
				if (!$newCurrencyFormatID) {
					$arErrorNewCurrencyFormat = OBX_CurrencyFormat::popLastError('ALL');
					$this->addError($arErrorNewCurrencyFormat["TEXT"], $arErrorNewCurrencyFormat["CODE"]);
					continue;
				}
				if ($newDefaultIndex !== false && $newCurrencyIndex == $newDefaultIndex) {
					OBX_Currency::setDefault($arNewCurrency["CURRENCY"]);
				}
				$countAllFormatsIsSuccess++;
			}
			if ($countAllFormatsIsSuccess == $countLangList) {
				$strNewSuccessIDList .= ((strlen($strNewSuccessIDList) > 0) ? ", " : "") . $arNewCurrency["CURRENCY"];
			} else {
				OBX_CurrencyFormat::deleteByFilter(array("CURRENCY" => $arNewCurrency["CURRENCY"]));
				OBX_Currency::delete($arNewCurrency["CURRENCY"]);
			}
		}
		if (strlen($strNewSuccessIDList) > 0) {
			$this->addMessage(GetMessage("OBX_SETT_CURRENCY_MESSAGE_1", array(
				"#CURRENCY_LIST#" => $strNewSuccessIDList
			)), 1);
		}
	}
}

class Settings_Price extends Settings {

	protected $listTableColumns = 7;

	public function showTabContent() {
		$arPriceList = Price::getListArray();
		$arCurrencyList = CurrencyFormat::getListArray(null, array("LANGUAGE_ID" => LANGUAGE_ID));?>
	<tr>
		<td>
			<table class="internal" style="width:100%">
		<tr class="heading">
			<td class="field-name"></td>
			<td style="width: 25px;">ID</td>
			<td><?=GetMessage("OBX_SETT_PRICE_F_NAME")?></td>
			<td><?=GetMessage("OBX_SETT_PRICE_F_CODE")?></td>
			<td><?=GetMessage("OBX_SETT_PRICE_F_SORT")?></td>
			<td><?=GetMessage("OBX_SETT_PRICE_F_CURRENCY")?></td>
			<td><?=GetMessage("OBX_SETT_PRICE_F_GROUPS")?></td>
			<td><?=GetMessage("OBX_SETT_PRICE_BTN_DELETE")?></td>
		</tr>
		<? foreach ($arPriceList as &$arPrice): ?>
			<tr>
				<td class="field-name"></td>
				<td><?=$arPrice["ID"]?><input type="hidden" name="obx_price_update[]" value="<?=$arPrice["ID"]?>"/></td>
				<td>
					<input type="text" name="obx_price[<?=$arPrice["ID"]?>][name]" value="<?=$arPrice["NAME"]?>"
						   placeholder="<?=GetMessage("OBX_SETT_PRICE_F_NAME")?>"/>
				</td>
				<td>
					<input type="text" name="obx_price[<?=$arPrice["ID"]?>][code]" value="<?=$arPrice["CODE"]?>"
						   placeholder="<?=GetMessage("OBX_SETT_PRICE_F_CODE")?>"/>
				</td>
				<td>
					<input type="text" name="obx_price[<?=$arPrice["ID"]?>][sort]" value="<?=$arPrice["SORT"]?>"
						   placeholder="<?=GetMessage("OBX_SETT_PRICE_F_SORT")?>"/>
				</td>
				<td>
					<select name="obx_price[<?=$arPrice["ID"]?>][currency]">
						<?foreach ($arCurrencyList as $arCurrency): ?>
						<option <?//
									?>value="<?=$arCurrency["CURRENCY"]?>"<?
							if ($arCurrency["CURRENCY"] == $arPrice["CURRENCY"] && $arPrice["CURRENCY_LANG_ID"] == LANGUAGE_ID) {
								?> selected="selected" <?
							}
							?>><?=$arCurrency["NAME"]?></option>
						<? endforeach?>
					</select>
				</td>
				<td>
					<div class="group_container">
						<?$curPriceGroups = Price::getGroupList($arPrice["ID"]);?>
						<?
						$i = 0;
						foreach ($curPriceGroups as $groupID):?>
							<div class="group_select">
								<select name="obx_price_ugrp[<?=$arPrice["ID"]?>][<?=$i?>]" data-price-id="<?=$arPrice["ID"]?>" data-count-id="<?=$i?>">
									<option value="-1">(<?=GetMessage("OBX_SETT_PRICE_DEL_GROUP")?>)</option>
									<?
									$rsGroups = \CGroup::GetList(($by = "c_sort"), ($order = "desc"), array("ACTIVE" => "Y"));
									while ($arGroup = $rsGroups->Fetch()):?>
										<option <?if ($arGroup["ID"] == $curPriceGroups[$i]): ?>selected=""<? endif;?>value="<?=$arGroup["ID"]?>">[<?=$arGroup["ID"]?>] <?=$arGroup["NAME"]?></option>
										<? endwhile;?>
								</select>
							</div>
							<?
							$i++;
						endforeach;
						?>
						<?if ($i==0):?>
						<div class="group_select">
							<select name="obx_price_ugrp[<?=$arPrice["ID"]?>][<?=$i?>]">
								<option value="-1">(<?=GetMessage("OBX_SETT_PRICE_DEL_GROUP")?>)</option>
								<?
								$rsGroups = \CGroup::GetList(($by = "c_sort"), ($order = "desc"), array("ACTIVE" => "Y"));
								while ($arGroup = $rsGroups->Fetch()):?>
									<option <?if ($arGroup["ID"] == $curPriceGroups[$i]): ?>selected=""<? endif;?>value="<?=$arGroup["ID"]?>">[<?=$arGroup["ID"]?>] <?=$arGroup["NAME"]?></option>
									<? endwhile;?>
							</select>
						</div>
						<?endif;?>
					</div>
					<a href="javascript:void(0)" class="bx-action-href add-new-group">Добавить группу доступа</a>
				</td>
				<td class="center">
					<input type="checkbox" name="obx_price_delete[<?=$arPrice["ID"]?>]" value="<?=$arPrice["ID"]?>"/>
				</td>
			</tr>
			<? endforeach; ?>
		<tr class="replace">
			<td colspan="<?=$this->listTableColumns?>"></td>
		</tr>
		<tr>
			<td class="field-name"></td>
			<td colspan="<?=$this->listTableColumns?>"><input class="add_new_item" type="button"
								   value="<?=GetMessage("OBX_SETT_PRICE_BTN_ADD_ITEM")?>"/></td>
		</tr>
		<tr>
			<td colspan="<?=$this->listTableColumns?>">
				<input type="button" id="obx_price_btn_save" value="<?=GetMessage("OBX_SETT_PRICE_BTN_SAVE")?>"/>
				<input type="button" id="obx_price_btn_cancel" value="<?=GetMessage("OBX_SETT_PRICE_BTN_CANCEL")?>"/>
			</td>
		</tr>
		<tr>
			<td class="delimiter hard" colspan="<?=$this->listTableColumns?>"></td>
		</tr>
			</table>
		</td>
	</tr>

	<?
	}

	public function showTabScripts() {
		$arCurrencyList = CurrencyFormat::getListArray(null, array("LANGUAGE_ID" => LANGUAGE_ID));

		if (false):?><table><? endif; // это надо для корректной подсветки html в NetBeans IDE?>
		<?=
		'<script type="text/x-custom-template" id="obx_market_price_row_tmpl">'
		; ?>
		<tr data-new-row="$index">
			<td class="field-name"></td>
			<td style="width: 25px;">$index</td>
			<td><input type="text" name="obx_price_new[$index][name]" value=""
					   placeholder="<?=GetMessage("OBX_SETT_PRICE_F_NAME")?>"/></td>
			<td><input type="text" name="obx_price_new[$index][code]" value=""
					   placeholder="<?=GetMessage("OBX_SETT_PRICE_F_CODE")?>"/></td>
			<td><input type="text" name="obx_price_new[$index][ыщке]" value=""
					   placeholder="<?=GetMessage("OBX_SETT_PRICE_F_SORT")?>"/></td>
			<td>
				<select name="obx_price_new[$index][currency]">
					<?foreach ($arCurrencyList as $arCurrency): ?>
					<option value="<?=$arCurrency["CURRENCY"]?>"><?=$arCurrency["NAME"]?></option>
					<? endforeach?>
				</select>
			</td>
			<td colspan="2" class="center">
				<input type="button" class="remove_new_item" value="<?=GetMessage("OBX_SETT_PRICE_BTN_DELETE")?>"/>
			</td>
		</tr>
		<tr class="replace">
			<td colspan="<?=$this->listTableColumns?>"></td>
		</tr>
		<?= '</script>' ?>
		<? if (false): ?></table><?endif;
	}

	public function saveTabData() {
		$this->updatePriceList();
		$this->deletePriceList();
		$this->addNewPriceList();
	}

	protected function updatePriceList() {
		if ((!is_array($_REQUEST["obx_price_update"])
			|| !is_array($_REQUEST["obx_price"]))
		) {
			return false;
		}
		$arPriceDeleteID = $_REQUEST["obx_price_delete"];
		$arPriceUpdateID = $_REQUEST["obx_price_update"];
		$arPriceData = $_REQUEST["obx_price"];

		$arPriceUserGroup = $_REQUEST["obx_price_ugrp"];

		$arLangList = self::getLangList();
		foreach ($arPriceDeleteID as &$delPriceID) {
			$delPriceID = intval($delPriceID);
		}
		$strUpdateSuccessID = '';
		foreach ($arPriceUpdateID as $priceID) {
			$priceID = intval($priceID);
			$arPriceExists = Price::getByID($priceID);
			if (!empty($arPriceExists)) {
				$arPriceExists;
				$arUpdateFields = array();
				if (!empty($arPriceData[$priceID]) && empty($arPriceDeleteID[$priceID])) {
					$arUpdateFieldsRaw = array(
						"ID" => $priceID,
						"CODE" => $arPriceData[$priceID]["code"],
						"CURRENCY" => $arPriceData[$priceID]["currency"],
						"NAME" => $arPriceData[$priceID]["name"],
					);
					foreach ($arUpdateFieldsRaw as $field => $rawValue) {
						if ($field == "ID") {
							$arUpdateFields[$field] = $rawValue;
						}
						if ($field != "ID"
							&& isset($arPriceExists[$field])
							&& $arPriceExists[$field] != $rawValue
						) {
							$arUpdateFields[$field] = $rawValue;
						}
					}
					if (count($arUpdateFields) > 1) {
						if (Price::update($arUpdateFields)) {
							$strUpdateSuccessID .= ((strlen($strUpdateSuccessID) > 0) ? ", " : "") . $priceID;
						} else {
							$arError = Price::popLastError('ALL');
							$this->addError($arError["TEXT"], $arError["CODE"]);
						}
					}
				}
			} else {
				$this->addError(GetMessage("OBX_SETT_PRICE_ERROR_1", array("#ID#" => $priceID)));
			}
			// UPDATE price groups
			$curGroups = $arPriceUserGroup[$priceID];
			Price::setGroupList($priceID,array_unique($curGroups));

		}
		if (strlen($strUpdateSuccessID) > 0) {
			$this->addMessage(GetMessage("OBX_SETT_PRICE_MESSAGE_2", array(
				"#ID_LIST#" => $strUpdateSuccessID
			)), 2);
		}
	}

	protected function deletePriceList() {
		if (!is_array($_REQUEST["obx_price_delete"])) {
			return false;
		}
		$arPriceUpdateID = $_REQUEST["obx_price_delete"];
		foreach ($arPriceUpdateID as $priceID => $delText) {
			$priceID = intval($priceID);
			if (!Price::delete($priceID)) {
				$this->addError(Price::popLastError());
			}
		}
	}

	protected function addNewPriceList() {
		if (!is_array($_REQUEST["obx_price_new"])) {
			return false;
		}

		$arNewPricesRaw = $_REQUEST["obx_price_new"];
		//d($arNewCurrenciesRaw, '$arNewCurrenciesRaw');
		$strNewSuccessID = '';
		foreach ($arNewPricesRaw as $arNewCurrencyRaw) {

			$arNewPriceFields = array(
				"NAME" => $arNewCurrencyRaw["name"],
				"CODE" => $arNewCurrencyRaw["code"],
				"CURRENCY" => $arNewCurrencyRaw["currency"],
			);
			$newPriceID = Price::add($arNewPriceFields);
			if (!$newPriceID) {
				$this->addError(Price::popLastError());
			} else {
				$strNewSuccessID .= ((strlen($strNewSuccessID) > 0) ? ", " : "") . $newPriceID;
			}
		}
		if (strlen($strNewSuccessID) > 0) {
			$this->addMessage(GetMessage("OBX_SETT_PRICE_MESSAGE_1", array(
				"#ID_LIST#" => $strNewSuccessID
			)), 1);
		}
	}
}

class Settings_Catalog extends Settings {

	protected $listTableColumns = 5;

	public function showTabContent() {
		$arECommerceIBlockList = ECommerceIBlock::getFullList();
		$arPriceList = Price::getListArray();
		?>
	<tr>
		<td>
			<table class="internal" style="width: 100%">
	<input type="hidden" name="obx_ecom_iblock_save" value="Y"/>
	<tr>
		<td colspan="<?=$this->listTableColumns?>">
			<input type="button" class="obx_ecom_iblock_save" value="<?=GetMessage("OBX_SETT_CATALOG_B_SAVE")?>"/>
			<input type="button" class="obx_ecom_iblock_cancel" value="<?=GetMessage("OBX_SETT_CATALOG_B_CANCEL")?>"/>
		</td>
	</tr>
	<tr class="heading">
		<td class="field-name"></td>
		<td><?=GetMessage("OBX_SETT_CATALOG_F_IBLOCK")?></td>
		<td><?=GetMessage("OBX_SETT_CATALOG_F_IBLOCK_TYPE")?></td>
		<td><?=GetMessage("OBX_SETT_CATALOG_F_IBLOCK_IS_ECOM")?></td>
		<td><?=GetMessage("OBX_SETT_CATALOG_F_PRICE")?></td>
		<td><?=GetMessage("OBX_SETT_CATALOG_F_IBLOCK_PROP")?></td>
	</tr>
	<?
		foreach ($arECommerceIBlockList as &$arIBlock) {
			$arPricePropList = CIBlockPropertyPrice::getFullPriceList($arIBlock["ID"]);
			$rsPropIntList = \CIBlockProperty::GetList(
				array("SORT" => "ASC"),
				array("IBLOCK_ID" => $arIBlock["ID"], "PROPERTY_TYPE" => "N")
			);
			$arPropIntList = array();
			while (($arPropInt = $rsPropIntList->GetNext())) {
				$arPropIntList[] = $arPropInt;
			}
			$countPricePropList = count($arPricePropList);
			$bIBockIsECom = ($arIBlock["IS_ECOM"] == "Y") ? true : false;
			?>
		<? /*/?>
			<tr>
				<td class="field-name"></td>
				<td><?wd($arPricePropList, '$arPricePropList')?></td>
			</tr>
			<?//*/
			?>
			<tr>
				<td class="field-name"></td>
			<td rowspan="<?=$countPricePropList?>" class="center"><?=$arIBlock["NAME"]?> (<?=$arIBlock["ID"]?>)</td>
			<td rowspan="<?=$countPricePropList?>" class="center"><?=$arIBlock["IBLOCK_TYPE_ID"]?></td>
			<td rowspan="<?=$countPricePropList?>" class="center">
				<label>
					<input name="obx_iblock_is_ecom[<?=$arIBlock["ID"]?>]"
						   class="obx_iblock_is_ecom"
						   data-checked-text="<?=GetMessage("OBX_SETT_CATALOG_L_DO_SIMPLE")?>"
						   data-unchecked-text="<?=GetMessage("OBX_SETT_CATALOG_L_DO_ECOM")?>"
						   data-iblock-id="<?=$arIBlock["ID"]?>"
						   type="checkbox"
						   value="Y"
						<?if ($bIBockIsECom): ?> checked="checked"<? endif?> />
						<span class="label-text">
							<?if (!$bIBockIsECom): ?><?= GetMessage("OBX_SETT_CATALOG_L_DO_ECOM") ?>
							<? else: ?><?= GetMessage("OBX_SETT_CATALOG_L_DO_SIMPLE") ?><?endif?>
						</span>
				</label>
			</td>

			<?
			$iProp = 0;
			foreach ($arPricePropList as &$arProp) {
				$iProp++;
				?>
				<? if ($iProp > 1): ?>
			</tr>
			<tr>
				<td class="field-name"></td>
					<? endif ?>
				<td>
					<div data-iblock-id="<?=$arIBlock["ID"]?>"
						 class="ibpprice-link-control<?if (!$bIBockIsECom): ?> iblock-is-not-ecom<? endif?>">
						<?=$arProp["PRICE_NAME"]?> [<?=$arProp["PRICE_CODE"]?>]
					</div>
				</td>
				<td>
					<div data-iblock-id="<?=$arIBlock["ID"]?>"
						 class="ibpprice-link-control<?if (!$bIBockIsECom): ?> iblock-is-not-ecom<? endif?>">
						<select <?if (!$bIBockIsECom): ?>disabled<?endif;?>
								name="obx_ib_price_prop[<?=$arIBlock["ID"]?>][<?=$arProp["PRICE_ID"]?>]">
							<option value="0">
								<?if ($arProp["PROPERTY_ID"] > 0): ?><?= GetMessage("OBX_SETT_CATALOG_S_REMOVE_LINK") ?>
								<? else: ?><?= GetMessage("OBX_SETT_CATALOG_S_DOESNOT_SET") ?><?endif?>
							</option>
							<option value="-1"><?=GetMessage("OBX_SETT_CATALOG_S_NEW_PROP")?></option>
							<?foreach ($arPropIntList as &$arPropInt): ?>
							<option value="<?=$arPropInt["ID"]?>"<?if ($arPropInt["ID"] == $arProp["PROPERTY_ID"]): ?>
									selected="selected"<? endif?>>
								<?=$arPropInt["NAME"]?>
								[<?=$arPropInt["ID"]?><?=((strlen($arPropInt["CODE"]) ? ":" : "") . $arPropInt["CODE"])?>
								]
							</option>
							<? endforeach?>
						</select>
					</div>
				</td>
				<?
			}
		}
		?>
			</tr>
	<tr>
		<td colspan="<?=$this->listTableColumns?>">
			<input type="button" class="obx_ecom_iblock_save" value="<?=GetMessage("OBX_SETT_CATALOG_B_SAVE")?>"/>
			<input type="button" class="obx_ecom_iblock_cancel" value="<?=GetMessage("OBX_SETT_CATALOG_B_CANCEL")?>"/>
		</td>
	</tr>
	<tr>
		<td class="delimiter hard" colspan="<?=$this->listTableColumns + 1?>"></td>
	</tr>
			</table>
		</td>
	</tr>

	<?
	}

	public function showTabScripts() {
	}

	public function saveTabData() {
		if (empty($_REQUEST["obx_ecom_iblock_save"])) {
			return true;
		}
		$rsIBlockList = ECommerceIBlock::getFullList(true);
		while (($arIBlock = $rsIBlockList->GetNext())) {
			if (array_key_exists($arIBlock["ID"], $_REQUEST["obx_iblock_is_ecom"])) {
				if ($arIBlock["IS_ECOM"] == "N") {
					ECommerceIBlock::add(array("IBLOCK_ID" => $arIBlock["ID"]));
				}
			} else {
				if ($arIBlock["IS_ECOM"] == "Y") {
					ECommerceIBlock::delete($arIBlock["ID"]);
				}
			}
		}
		if (empty($_REQUEST["obx_ib_price_prop"])) {
			return true;
		}
		$arIBlockList = ECommerceIBlock::getFullList(false);
		foreach ($arIBlockList as &$arIBlock) {
			if ($arIBlock["IS_ECOM"] == "N" || !isset($_REQUEST["obx_ib_price_prop"][$arIBlock["ID"]])) {
				continue;
			}
			$arIBPricePropFullList = CIBlockPropertyPrice::getFullPropList($arIBlock["ID"]);
			$rawSetPriceProp = $_REQUEST["obx_ib_price_prop"][$arIBlock["ID"]];
			$arNewPricePropLinkList = array();
			$arUniquePR = array();
			$arUniquePP = array();
			foreach ($rawSetPriceProp as $priceID => $propID) {
				$priceID = intval($priceID);
				$propID = intval($propID);
				if ($priceID > 0) {
					if ($propID > 0) {
						$keyPR = $arIBlock["ID"] . "_" . $priceID;
						$keyPP = $arIBlock["ID"] . "_" . $propID;
						if (array_key_exists($keyPR, $arUniquePR)) {
							$this->addError(GetMessage("OBX_SETT_CATALOG_ERROR_1", array(
								"#IBLOCK_ID#" => $arIBlock["ID"]
							)), 1);
							return false;
						}
						if (array_key_exists($keyPP, $arUniquePP)) {
							$this->addError(GetMessage("OBX_SETT_CATALOG_ERROR_2", array(
								"#IBLOCK_PROP_ID#" => htmlspecialcharsEx($propID)
							)), 2);
							return false;
						}
						$arUniquePR[$keyPR] = true;
						$arUniquePP[$keyPP] = true;
						$arNewPricePropLinkList[] = array(
							"__ACTION" => "ADD",
							"IBLOCK_ID" => $arIBlock["ID"],
							"PRICE_ID" => $priceID,
							"IBLOCK_PROP_ID" => $propID
						);
					} elseif ($propID == 0) {
						$arDelFilter = array(
							"IBLOCK_ID" => $arIBlock["ID"],
							"PRICE_ID" => $priceID,
						);
						$arExists = CIBlockPropertyPrice::getListArray(null, $arDelFilter, null, null, null, false);
						if (!empty($arExists)) {
							$arExists["__ACTION"] = "DELETE";
							$arNewPricePropLinkList[] = $arExists;
						}
					} elseif ($propID == -1) {
						$arNewPricePropLinkList[] = array(
							"__ACTION" => "NEW_PROP",
							"IBLOCK_ID" => $arIBlock["ID"],
							"PRICE_ID" => $priceID,
						);
					}
				}
			}
			CIBlockPropertyPrice::deleteByFilter(array("IBLOCK_ID" => $arIBlock["ID"]));
			CIBlockPropertyPrice::clearErrors();
			foreach ($arNewPricePropLinkList as &$arNewPricePropLink) {
				if ($arNewPricePropLink["__ACTION"] == "ADD") {
					$bSuccess = CIBlockPropertyPrice::add($arNewPricePropLink);
				} //				elseif($arNewPricePropLink["__ACTION"] == "DELETE") {
//					$bSuccess = CIBlockPropertyPrice::deleteByFilter($arNewPricePropLink);
//				}
				elseif ($arNewPricePropLink["__ACTION"] == "NEW_PROP") {
					$bSuccess = CIBlockPropertyPrice::addIBlockPriceProperty($arNewPricePropLink);
				}
				if (!$bSuccess) {
					$arError = CIBlockPropertyPrice::popLastError('ALL');
					$this->addError($arError["TEXT"], $arError["CODE"]);
				}
			}
		}
		return true;
	}
}
