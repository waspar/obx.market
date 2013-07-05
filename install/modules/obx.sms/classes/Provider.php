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

namespace OBX\Sms\Provider;

use OBX\Core\CMessagePoolDecorator;

abstract class Provider extends CMessagePoolDecorator {
	static protected $_arProvidersList;
	/**
	 * @var self (Singleton)
	 */
	static protected $_arProviderListClassIndex;

	protected $PROVIDER_ID = "";
	protected $PROVIDER_NAME = "";
	protected $PROVIDER_DESCRIPTION = "";

	/**
	 * Параметры
	 * @var \OBX\Core\Settings\Settings | null
	 */
	protected $_Settings = null;

	protected $_lastSentMessage = null;
	const DEFAULT_COUNTRY_CODE = '7';

	/**
	 * В конструкторе обязательно надо определить переменную $this->_Settings
	 */
	abstract protected function __construct();

	final protected function __clone() {}

	public function getSettings($bReturnArray = false) {
		if($bReturnArray) {
			return $this->_Settings->getSettings();
		}
		return $this->_Settings;
	}
	public function syncSettings() {
		$this->_Settings->syncSettings();
	}

	public function saveSettings($arSettings) {
		$this->_Settings->saveSettings($arSettings);
	}

	public function saveSettingsRequestData() {
		$this->_Settings->saveSettingsRequestData();
	}

	/**
	 * @return string
	 */
	final public function PROVIDER_DESCRIPTION() {
		return $this->PROVIDER_DESCRIPTION;
	}

	/**
	 * @return string
	 */
	final public function PROVIDER_NAME() {
		return $this->PROVIDER_NAME;
	}

	/**
	 * @return string
	 */
	final public function PROVIDER_ID() {
		return $this->PROVIDER_ID;
	}

	/**
	 *
	 */
	final static public function registerProvider() {
		$className = get_called_class();
		/**
		 * @var Provider $Provider
		 */
		$Provider = new $className;
		if ($Provider instanceof self) {
			if (!array_key_exists($Provider->PROVIDER_ID(), self::$_arProvidersList)) {
				/** @var Provider $Provider */
				$Provider->syncSettings();
				self::$_arProvidersList[$Provider->PROVIDER_ID()] = $Provider;
				self::$_arProviderListClassIndex[$className] = $Provider;
			}
		}
	}

	/**
	 * @return null | self
	 */
	final static public function getProvider() {
		$className = get_called_class();
		if( array_key_exists($className, self::$_arProviderListClassIndex) ) {
			return self::$_arProviderListClassIndex[$className];
		}
		return null;
	}

	/**
	 * @return bool
	 */
	final static public function includeProviders() {
		$_providerDir = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/php_interface/obx.sms';
		$_providerLangDir = $_providerDir.'/lang/'.LANGUAGE_ID;

		if (!is_dir($_providerDir)) {
			return false;
		}

		$dir = opendir($_providerDir);
		while ($elementOfDir = readdir($dir)) {
			if (
					$elementOfDir != ".."
					&& $elementOfDir != "."
					&& substr($elementOfDir, strlen($elementOfDir) - 4, strlen($elementOfDir)) == '.php'
			) {
				$arFilesList[] = $elementOfDir;
			}
		}

		foreach ($arFilesList as $providerFileName) {
			__IncludeLang($_providerLangDir.'/'.$providerFileName);
			@include $_providerDir.'/'.$providerFileName;
		}
		return self::$_arProvidersList;
	}

	/**
	 * @return mixed
	 */
	final static public function getProvidersList() {
		return self::$_arProvidersList;
	}

	/**
	 * @param $providerID
	 * @return null
	 */
	final static public function factory($providerID) {
		if (array_key_exists($providerID, self::$_arProvidersList)) {
			return self::$_arProvidersList[$providerID];
		} else {
			/**
			 * @var \CMain $APPLICATION
			 */
			global $APPLICATION;
			$APPLICATION->ThrowException(GetMessage("OBX_SMS_PROVIDER_NOT_FOUND"));
			return null;
		}
	}

	public function checkPhoneNumber($rawPhoneNumber, &$countryCode = null) {
		$rawPhoneNumber = str_replace(array(' ', '	', '-', '(', ')'), '', $rawPhoneNumber);
		$regPhone = '~((?:\+)?[\d]{1,3}|8)([\d]{10})~';
		$phoneNumber = null;
		if( preg_match($regPhone, $rawPhoneNumber, $arMatches) ) {
			if($arMatches[1] == 8) $arMatches[1] = '7';
			$countryCode = $arMatches[1];
			if(strpos($countryCode, '+')!==false) {
				$countryCode = substr($countryCode, 1);
			}
			$phoneNumber = $arMatches[2];
		}
		return $phoneNumber;
	}

	/**
	 * Простая отправка сообщений
	 * один номер - один текст сообщения
	 * @param $telNo
	 * @param $text
	 * @param array $arFields
	 * @return bool
	 */
	public function send($telNo, $text, $arFields = array()) {
		$phoneNumber = $this->checkPhoneNumber($telNo, $countryCode);
		if($phoneNumber == null) {
			$this->addError(GetMessage('OBX_SMS_PROVIDER_ERROR_801'), 801);
			return false;
		}
		if( empty($countryCode) ) $countryCode = static::DEFAULT_COUNTRY_CODE;
		$bSuccess = $this->_send($phoneNumber, $text, $arFields, $countryCode);
		$this->_lastSentMessage = array(
			'COUNTRY' => $countryCode,
			'PHONE' => $phoneNumber,
			'TEXT' => $text,
			'SUCCESS' => $bSuccess?'Y':'N',
		);
		if(!$bSuccess) $this->_lastSentMessage['ERROR'] = $this->getLastError();
		return $bSuccess;
	}

	/**
	 * Эта функция переопределяется у каждого провайдера отдельно
	 * @param $telNo
	 * @param $text
	 * @param $arFields
	 * @param string $countryCode
	 * @return mixed
	 */
	abstract protected function _send(&$telNo, &$text, &$arFields, &$countryCode);

	/**
	 * Вернуть последнее отправленное сообщение
	 * @return array
	 */
	public function getLastSentMessage() {
		return $this->_lastSentMessage;
	}

	/**
	 * TODO:
	 * список номеров - один текст сообщения
	 */
	public function sendBatch() {
	}

	/*
	 * Персональная отправка сообщений
	 * TODO: to @version 0.5.0
	 * send = Один номер - один шаблон сообщения
	 * sendBatch = Список персон - один шаблон
	 */

	/**
	 * @return float
	 */
	abstract public function getBalance();

	/**
	 * @param $messageID
	 * @return mixed
	 */
	abstract public function getMessageStatus($messageID);


	/**
	 * Получить объект провайдера по умолчанию
	 * @return Provider
	 */
	final static public function getCurrent() {
		$curProvID = \COption::GetOptionString('obx.sms', 'PROVIDER_SELECTED');
		if (strlen($curProvID) > 0) {
			return self::factory($curProvID);
		}
	}

	/**
	 * Задать провайдера по умолчанию
	 * Возвращает true в случае успешной установки или false в случае если $providerID не найден в списке провайдеров
	 * @param string $providerID
	 * @return bool
	 */
	final static public function setCurrent($providerID) {
		if (array_key_exists($providerID, self::$_arProvidersList)) {
			\COption::SetOptionString('obx.sms', 'PROVIDER_SELECTED', $providerID);
			return true;
		}
		return false;
	}
}
