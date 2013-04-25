<?php
class OBX_Test_Currency2 extends OBX_Market_TestCase
{
	private $_CurrencyDBS = null;
	private $_arResult = array();
	private $_arTestCurrencies = array();
	public function setUp() {
		$this->_CurrencyDBS = OBX_CurrencyDBS::getInstance();
		$this->_arTestCurrencies = array(
			'TUG' => array(
				'COURSE' => '1',
				'RATE' => '1',
				'IS_DEFAULT' => 'N',
				'FORMAT' => array(
					'ru' => array(
						'NAME' => 'Тугрики',
						'FORMAT' => '# туг.',
						'THOUSANDS_SEP' => ' ',
						'DEC_POINT' => ','
					),
					'en' => array(
						'NAME' => 'Tugriki',
						'FORMAT' => '# tug.',
						'THOUSANDS_SEP' => '\'',
						'DEC_POINT' => '.'
					)
				)
			),
			'TUK' => array(
				'COURSE' => '1',
				'RATE' => '1',
				'IS_DEFAULT' => 'N',
				'FORMAT' => array(
					'ru' => array(
						'NAME' => 'Тукрики',
						'FORMAT' => '# туг.',
						'THOUSANDS_SEP' => ' ',
						'DEC_POINT' => ','
					),
					'en' => array(
						'NAME' => 'Tukriki',
						'FORMAT' => '# tug.',
						'THOUSANDS_SEP' => '\'',
						'DEC_POINT' => '.'
					)
				)
			),
		);
	}
}