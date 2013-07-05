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

use OBX\Core\Settings\Tab;

class KompeitoSms extends Provider {

	/*
	 * Объявление провайдера
	 */
	protected $PROVIDER_ID = 'KOMPEITOSMS';
	protected $PROVIDER_NAME = null;
	protected $PROVIDER_DESCRIPTION = null;


	public function __construct() {
		$this->PROVIDER_NAME = GetMessage('OBX_SMS_PROVIDER_KOMPEITOSMS_NAME');
		$this->PROVIDER_DESCRIPTION = GetMessage('OBX_SMS_PROVIDER_KOMPEITOSMS_DECRIPTION');
		$this->_Settings = new Tab(
			'obx.sms',
			'PROVIDER_'.$this->PROVIDER_ID(),
			array(
				'LOGIN' => array(
					'NAME' => GetMessage('OBX_SMS_PROV_KOMPEITOSMS_SETT_LOGIN'),
					'TYPE' => 'STRING',
					'VALUE' => '',
					'INPUT_ATTR' => array(
						'placeholder' => GetMessage('OBX_SMS_PROV_KOMPEITOSMS_SETT_LOGIN_PH')
					)
				),
				'PASS' => array(
					'NAME' => GetMessage('OBX_SMS_PROV_KOMPEITOSMS_SETT_PASS'),
					'TYPE' => 'PASSWORD',
					'VALUE' => '',
					'INPUT_ATTR' => array(
						'placeholder' => GetMessage('OBX_SMS_PROV_KOMPEITOSMS_SETT_PASS_PH')
					)
				),
				'FROM' => array(
					'NAME' => GetMessage('OBX_SMS_PROV_KOMPEITOSMS_SETT_FROM'),
					'TYPE' => 'STRING',
					'VALUE' => '',
					'INPUT_ATTR' => array(
						'placeholder' => GetMessage('OBX_SMS_PROV_LETSADS_SETT_FROM_PH')
					)
				)
			)
		);
	}


	const STATE_QUEUED = 0;
	const STATE_INVALID_DST = 2;
	const STATE_INVALID_SRC = 3;
	const STATE_NO_MONEY = 4;
	const STATE_DIR_BLOCKED = 5;
	const STATE_LIMIT = 7;
	const STATE_MESSAGE_EMPTY = 50;
	const STATE_SERVICE_UNAVAILABLE = 100;

	const DELIVERY_WAITING = 1;
	const DELIVERY_SENT = 2;
	const DELIVERY_DELIVERED = 4;
	const DELIVERY_REJECTED = 5;
	const DELIVERY_FAILED = 6;

	const ADDR = 'https://cabinet.kompeito.ru:443/api/xml';

	/*
		public function __construct($user, $pass, $from=null) {
			$this->from = $from;
			$this->user = $user;
			$this->pass = $pass;
		}
	*/

	public function getMessageStatus($messageID) {
		return $this->getStatus($messageID);
	}

	protected function _send(&$phoneNumber, &$text, &$arFields, &$countryCode) {
		return $this->sendEx($this->from, $countryCode.$phoneNumber, $text);
	}

	public function sendSingle($to, $message) {
		$tmp = $this->sendEx($this->from, $to, $message);
		$result = array();
		if (array_key_exists('error', $tmp)) {
			$result['error'] = $tmp['error'];
		}
		if (array_key_exists('count', $tmp)) {
			$result['count'] = $tmp['count'];
		}
		if (array_key_exists('data', $tmp) && is_array($tmp['data']) && count($tmp['data']) > 0) {
			return array_merge($result, $tmp['data'][0]);
		}
		return $result;
	}

	public function getStatusSingle($id) {
		while (is_array($id)) {
			$id = $id[0];
		}
		$result = $this->getStatus($id);
		if (array_key_exists('error', $result)) {
			return $result;
		} else {
			if (count($result) > 0) {
				return $result[0];
			} else {
				return null;
			}
		}
	}

	public function getStatus($ids) {
		$xml = new \SimpleXMLElement('<getStatus></getStatus>');
		$this->addAuth($xml);
		if (is_array($ids)) {
			foreach ($ids as $i => $id) {
				$xml->sms[$i]['id'] = $id;
			}
		} else {
			$xml->sms['id'] = $ids;
		}
		$response = $this->doSend($xml);
		$result = array();
		if ($this->starts_with('<?xml', $response)) {
			$xmlRes = new \SimpleXMLElement($response);
			foreach ($xmlRes->sms as $tmp) {
				$tmpResult = array();
				$tmpResult['id'] = (string)$tmp['id'];
				$tmpResult['req'] = date_create($tmp->requestTime);
				$parts = array();
				foreach ($tmp->part as $j => $p) {
					$partData = array();
					$partData['id'] = (string)$p['id'];
					$partData['status'] = (int)$p['status'];
					if ($p->completionTime) {
						$partData['fin'] = date_create($p->completionTime);
					}
					$parts[$j] = $partData;
				}
				$tmpResult['parts'] = $parts;
				array_push($result, $tmpResult);
			}
		} else {
			$result['error'] = (string)$response;
		}
		return $result;
	}

	public function getBalance() {
		$result = $this->requestBalance();
		return $result['money'];
	}

	public function requestBalance() {
		$xml = new \SimpleXMLElement('<getBalance></getBalance>');
		$this->addAuth($xml);
		$response = $this->doSend($xml);
		$result = array();
		if ($this->starts_with('<?xml', $response)) {
			$xmlResult = new \SimpleXMLElement($response);
			$result['money'] = (double)$xmlResult->money;
			$result['credits'] = (double)$xmlResult->credits;
			$result['holdMoney'] = (double)$xmlResult->holdMoney;
			$result['holdCredits'] = (double)$xmlResult->holdCredits;
			$result['overdraft'] = (double)$xmlResult->overdraft;
		} else {
			$result['error'] = (string)$response;
		}
		return $result;
	}

	public function sendEx($from, $to, $message) {
		$xmlResult = new \SimpleXMLElement('<sendSms></sendSms>');
		$this->addAuth($xmlResult);

		$xmlResult->from = $from;

		if (is_array($to)) {
			foreach ($to as $i => $t) {
				$xmlResult->to[$i] = $t;
			}
		} else {
			$xmlResult->to = $to;
		}

		$xmlResult->message = $message;


		$response = $this->doSend($xmlResult);
		$result = array();
		if ($this->starts_with('<?xml', $response)) {
			$xml = new \SimpleXMLElement($response);
			$result['count'] = (int)$xml->count;
			$result['data'] = array();
			foreach ($xml->to as $tmp) {
				$a = $tmp->attributes();
				$c = $tmp->children();
				$rep = array(
					'id' => (string)($a['id']),
					'to' => (string)$a['phone'],
					'status' => (int)$a['status'],
					'credits' => (double)$c['credits'],
					'money' => (double)$c['money']
				);
				array_push($result['data'], $rep);
			}
		} else {
			$result['error'] = (string)$response;
		}
		return $result;
	}

	private function starts_with($str, $src) {
		return substr($src, 0, strlen($str)) == $str;
	}

	private function addAuth($xml) {
		$xml->auth->login = $this->arSettings['LOGIN']['VALUE'];
		$xml->auth->pass = $this->arSettings['PASS']['VALUE'];
	}

	private function doSend($xml) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::ADDR);
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());

		$result = curl_exec($ch);

		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != 200) {
			return 'HTTP: ' . $info['http_code'];
		}

		return $result;
	}
}

//KompeitoSms::registerProvider();
