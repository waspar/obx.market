<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market;

use OBX\Core\Tools;
use OBX\Core\CMessagePoolStatic;
use OBX\Core\CMessagePoolDecorator;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;

class CurrencyInfo extends CMessagePoolDecorator
{
	/**
	 * @var array
	 */
	static protected $_arInstances = null;

	/**
	 * @param $currency - валюта
	 * @param bool $bUpdateInstance - обновить если закешировано
	 * @return null | self
	 */
	static public function & getInstance($currency, $bUpdateInstance = false) {
		$isCorrectCurrency = CurrencyDBS::getInstance()->__check_CURRENCY($currency);
		if(!$isCorrectCurrency) {
			return null;
		}
		if(self::$_arInstances[$currency] == null) {
			self::$_arInstances[$currency] = new self($currency);
		}
		elseif($bUpdateInstance) {
			self::$_arInstances[$currency]->updateInfo();
		}
		return self::$_arInstances[$currency];
	}
	static public function clearInstance($currency) {
		if( array_key_exists($currency, self::$_arInstances) ) {
			self::$_arInstances[$currency] = null;
			return true;
		}
		return false;
	}

	/**
	 * @var null|CurrencyDBS
	 */

	protected $_CurrencyDBS = null;
	/**
	 * @var null|CurrencyFormatDBS
	 */
	protected $_CurrencyFormatDBS = null;

	protected $_arCurrencyFields = array();

	public function __construct($currency) {
		$this->_CurrencyDBS = CurrencyDBS::getInstance();
		$this->_CurrencyFormatDBS = CurrencyFormatDBS::getInstance();
		$bSuccess = $this->_updateInfo($currency);
		if( !$bSuccess ) {
			$this->addError($this->_CurrencyDBS->getLastError());
		}
	}

	public function updateInfo() {
		return $this->_updateInfo($this->_arCurrencyFields['CURRENCY']);
	}

	public function _updateInfo($currency) {
		$arCurrency = $this->_CurrencyDBS->getByID($currency);
		if( empty($arCurrency) ) {
			self::clearInstance($currency);
			return false;
		}
		$arCurrencyFormatList = $this->_CurrencyFormatDBS->getListArray(
			array('LANGUAGE_SORT' => 'ASC'),
			array('CURRENCY' => $currency),
			null, null
		);
		$arCurrency['FORMAT'] = array();
		foreach($arCurrencyFormatList as &$arCurrencyFormat) {
			$arCurrency['FORMAT'][$arCurrencyFormat['LANGUAGE_ID']] = $arCurrencyFormat;
		}
		$this->_arCurrencyFields = $arCurrency;
		return true;
	}

	public function getFields() {
		return $this->_arCurrencyFields;
	}
}