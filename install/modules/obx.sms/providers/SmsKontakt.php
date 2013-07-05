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

namespace OBX\Sms\Provider;
use OBX\Core\Settings\Tab;

IncludeModuleLangFile(__FILE__);

class SmsKontakt extends Provider {

	/*
	 * Объявление провайдера
	 */
	protected $PROVIDER_ID = 'SMSKONTAKT';
	protected $PROVIDER_NAME = null;
	protected $PROVIDER_DESCRIPTION = null;

	const URL_INFO = 'http://sms-kontakt.ru/api/get_info/';
	const URL_SEND = 'http://sms-kontakt.ru/api/message/send/';

	protected $sign;

	protected function __construct() {
		$this->PROVIDER_NAME = GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_NAME');
		$this->PROVIDER_DESCRIPTION = GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_DESCRIPTION');
		$this->_Settings = new Tab(
			'obx.sms',
			'PROVIDER_'.$this->PROVIDER_ID,
			array(
				'USER_PHONE' => array(
					'NAME' => GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_SETT_USER_PHONE'),
					'TYPE' => 'STRING',
					'VALUE' => '',
					'INPUT_ATTR' => array(
						'placeholder' => GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_SETT_USER_PHONE_PH')
					)
				),
				'API_KEY' => array(
					'NAME' => GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_SETT_API_KEY'),
					'TYPE' => 'STRING',
					'VALUE' => '',
				),
				'SENDER_ID' => array(
					'NAME' => GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_SETT_SENDER_ID'),
					'TYPE' => 'STRING',
					'VALUE' => ''
				),
				'TEST' => array(
					'NAME' => GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_SETT_TEST'),
					'TYPE' => 'CHECKBOX',
					'VALUE' => 'Y'

				)
			)
		);
		$this->sign = md5($this->_Settings->getOption('USER_PHONE') . $this->_Settings->getOption('API_KEY'));
	}

	public function getBalance() {
		$arResult = json_decode($this->_getBalance(), true);
		if ($arResult[0]['result'] == 'success') {
			return $arResult[0]['describe'];
		} else {
			$this->addError($arResult[0]['describe'], self::SEND_STATUS_FAIL);
			return self::SEND_STATUS_FAIL;
		}
	}

	public function getMessageStatus($messageID) {
		return 1;
	}

	protected function _send(&$phoneNumber, &$text, &$arFields, &$countryCode) {
		if( $countryCode != '7') {
			$this->addError(GetMessage('OBX_SMS_PROVIDER_SMSKONTAKT_ERROR_1'));
			return false;
		}
		$result = $this->MessageSend($phoneNumber, $text);
		$arResult = json_decode($result, true);
		if ($arResult[0]['result'] == 'success') {
			return true;
		} else {
			$this->addError($arResult[0]['describe']);
			return false;
		}
	}

	function SendPostRequest($url, $headers, $post_body) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); // урл страницы
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body); // передаём post-данные
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //
		$result = curl_exec($ch); // получить результат в переменную
		curl_close($ch);
		return $result;
	}

	protected function MessageSend($phone_to, $message) {
		$curSettings = $this->_Settings->getSettings();

		$user_phone = $curSettings['USER_PHONE']['VALUE'];
		$sender_id = $curSettings['SENDER_ID']['VALUE'];
		$test = ($curSettings['TEST']['VALUE']=='Y')?1:0;


		$http_body = 'user_phone=' . $user_phone . '&sign=' .
			$this->sign . '&phone_to=' . $phone_to . '&message=' . $message . '&sender_id=' . $sender_id . '&test=' . $test;
		$headers[] = 'Content-Type: text/xml; charset=utf-8';
		$headers[] = 'Content-Length: ' . strlen($http_body);
		if(!defined('BX_UTF') || BX_UTF == false) {
			$http_body = iconv(LANG_CHARSET, 'UTF-8', $http_body);
		}

		$server_answer = $this->SendPostRequest(self::URL_SEND, $headers, $http_body);
		return $server_answer;
	}

	protected  function _getBalance() {
		//?user_phone=<номер_телефона>&sign=<подпись_сообщения>&info=balance
		$curSettings = $this->_Settings->getSettings();

		$user_phone = $curSettings['USER_PHONE']['VALUE'];
		$sender_id = $curSettings['SENDER_ID']['VALUE'];
		$test = $curSettings['TEST']['VALUE'];

		$http_body = 'user_phone='.$user_phone . '&sign='.$this->sign .'&info=balance';
		$headers[] = 'Content-Length: ' . strlen($http_body);
		$headers[] = 'Content-Type: text/xml; charset=utf-8';

		$server_answer = $this->SendPostRequest(self::URL_INFO, $headers, $http_body);
		return $server_answer;
	}
}

SmsKontakt::registerProvider();