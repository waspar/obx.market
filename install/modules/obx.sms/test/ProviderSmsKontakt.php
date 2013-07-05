<?php
namespace OBX\Sms\Test;
use OBX\Sms\Provider\Provider;
use OBX\Sms\Provider\SmsKontakt;



class ProviderSmsKontakt extends SmsTestCase {
	/**
	 * @var Provider
	 */
	protected $_Provider = null;
	public function setUp() {
		$this->_Provider = SmsKontakt::getProvider();
	}
	public function testGetBalance() {
		$balance = $this->_Provider->getBalance();
		$debug=1;
	}
}