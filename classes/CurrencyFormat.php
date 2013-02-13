<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

IncludeModuleLangFile(__FILE__);

class OBX_CurrencyFormatDBS extends OBX_DBSimple
{
	protected $_arTableList = array(
		'C' => 'obx_currency',
		'L' => 'b_language',
		'F' => 'obx_currency_format'
	);
	protected $_mainTable = 'F';
	protected $_arTableLinks = array(
		0 => array(
			array('F' => 'CURRENCY'),
			array('C' => 'CURRENCY')
		)
		,1 => array(
			array('L' => 'LID'),
			array('F' => 'LANGUAGE_ID')
		)
	);

	protected $_arTableFields = array(
		'ID'						=> array('F'	=> 'ID'),
		'CURRENCY'					=> array('C'	=> 'CURRENCY'),
		'LANGUAGE_ID'				=> array('L'	=> 'LID'),
		'LANGUAGE_NAME'				=> array('L'	=> 'NAME'),
		'LANGUAGE_SORT'				=> array('L'	=> 'SORT'),
		'NAME'						=> array('F'	=> 'NAME'),
		'FORMAT'					=> array('F'	=> 'FORMAT'),
		'THOUSANDS_SEP'				=> array('F'	=> 'THOUSANDS_SEP'),
		'DEC_PRECISION'				=> array('F'	=> 'DEC_PRECISION'),
		'DEC_POINT'					=> array('F'	=> 'DEC_POINT'),
		'CURRENCY_SORT'				=> array('C'	=> 'SORT'),
		'CURRENCY_COURSE'			=> array('C'	=> 'COURSE'),
		'CURRENCY_RATE'				=> array('C'	=> 'RATE'),
		'CURRENCY_IS_DEFAULT'		=> array('C'	=> 'IS_DEFAULT')
	);
	protected $_arTableUnique = array(
		'udx_obx_currency_format' => array('CURRENCY', 'LANGUAGE_ID')
	);
	protected $_arTableLeftJoin = array(
		'L' => 'true',
		'F' => 'L.LID = F.LANGUAGE_ID AND C.CURRENCY = F.CURRENCY'
	);
	protected $_arTableFieldsDefault = array(
		'LANGUAGE_ID' => LANGUAGE_ID,
		'FORMAT' => '#',
		'THOUSANDS_SEP' => '',
		'DEC_PRECISION' => 2,
		'DEC_POINT' => '.'
	);
	protected $_arTableJoinNullFieldDefaults = array(
		'FORMAT' => '#',
		'THOUSANDS_SEP' => '',
		'DEC_PRECISION' => 2,
		'DEC_POINT' => '.'
	);
	protected $_arSortDefault = array();
	// не очень удобно получается. Проще местами его указывать, чем сбратывать там где надо полный список
	//protected $_arFilterDefault = array(
	//	'LANGUAGE_ID' => LANGUAGE_ID
	//);
	protected $_arTableFieldsCheck = array();
	function __construct() {
		$this->_arTableFieldsCheck = array(
			'ID'						=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'CURRENCY'					=> self::FLD_T_CODE | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'LANGUAGE_ID'				=> self::FLD_T_BX_LANG_ID | self::FLD_DEFAULT | self::FLD_REQUIRED,
			'NAME'						=> self::FLD_T_STRING | self::FLD_NOT_NULL | self::FLD_REQUIRED,
			'FORMAT'					=> self::FLD_T_STRING | self::FLD_DEFAULT,
			'THOUSANDS_SEP'				=> self::FLD_T_CHAR | self::FLD_DEFAULT,
			'DEC_PRECISION'				=> self::FLD_T_INT | self::FLD_DEFAULT
		);
		$this->_arDBSimpleLangMessages = array(
			// Не заполнено обязательное поле Валюта
			'REQ_FLD_CURRENCY' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_1'),
				'CODE' => 1
			),
			// Не заполнено обязательное поле Имя валюты
			'REQ_FLD_NAME' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_2'),
				'CODE' => 2
			),
			// Ошибка добавления. Дубль по первичному ключу
			'DUP_PK' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_3'),
				'CODE' => 3
			),
			// Ошибка добавления. Дубль по уникальному индкесу
			'DUP_ADD_udx_obx_currency_format' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_4'),
				'CODE' => 4
			),
			// Ошибка обновления. Дубль по уникальному индкесу
			'DUP_UPD_udx_obx_currency_format' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_7'),
				'CODE' => 7
			),
			// Ошибка удаления. Запись не найдена
			'NOTHING_TO_DELETE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_5'),
				'CODE' => 5
			),
			// Ошибка обновления. Запись не найдена
			'NOTHING_TO_UPDATE' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_CURRENCY_FORMAT_ERROR_6'),
				'CODE' => 6
			)
		);
	}

	public function getListGroupedByLang($arSort = null) {
		$rsLang = CLanguage::GetList($by='sort', $sort='asc', $arLangFilter=array('ACTIVE' => 'Y'));
		$arLangList = array();
		$arLangExistsReset = array();
		while( $arLang = $rsLang->Fetch() ) {
			$arLangList[$arLang['ID']] = $arLang;
			$arLangExistsReset[$arLang['ID']] = false;
		}
		$countLangList = count($arLangList);

		$arCurrencyLangList = array();
		$rsFormatList = $this->getList($arSort);
		$arTableJoinNullFieldDefaults = $this->_arTableJoinNullFieldDefaults;
		$countTableJoinNullFieldDefaults = count($arTableJoinNullFieldDefaults);
		while( ($arFormat = $rsFormatList->Fetch()) ) {
			foreach($arFormat as $fieldName => &$fieldValue) {
				if( empty($fieldValue)
					&& $countTableJoinNullFieldDefaults>0
					&& array_key_exists($fieldName, $arTableJoinNullFieldDefaults)
				) {
					$fieldValue = $arTableJoinNullFieldDefaults[$fieldName];
				}
				$arField = $this->_arTableFields[$fieldName];
				list($tblAlias, $tblFldName) = each($arField);
				if($tblAlias == 'C') {
					$arCurrencyLangList[$arFormat['CURRENCY']][$tblFldName] = $fieldValue;
				}
				else {
					$arCurrencyLangList[$arFormat['CURRENCY']]['LANG'][$arFormat['LANGUAGE_ID']][$fieldName] = $fieldValue;
				}
			}
		}
		return $arCurrencyLangList;
	}

	public function formatPrice($priceValue, $currencyCode = null, $langID = null, $arFormat = null) {
		if( !is_numeric($priceValue) ) {
			$this->addWarning(GetMessage('OBX_MARKET_CURRENCY_WARNING_2'), 2);
			return $priceValue;
		}

		if( is_array($arFormat) ) {
			foreach($this->_arTableDefaultFields as $formatKey => &$fmtUnitVal)
				if( empty($arFormat[$formatKey]) ) {
					$arFormat[$formatKey] = $fmtUnitVal;
				}
		}
		else {
			if(false) {
				// TODO: Дописать для самостоятельного использования Пока подходит только для работы через класс OBX_Price
			}
			else {
				$arFormat = $this->_arTableDefaultFields;
			}
		}

		$priceValue = floatval($priceValue);
		$valueInt = round($priceValue, 0);
		$valueDec = round($priceValue*pow(10, $arFormat['DEC_PRECISION']) - $valueInt*pow(10, $arFormat['DEC_PRECISION']), 0);
		if(!$valueInt) {
			$valueInt = '0';
		}
		$cent = '%';
		if(!$valueDec) {
			//$valueDec = '00';
			$valueDec = '';
			if( strpos($arFormat['FORMAT'], ',%')!==false ) {
				$valueDec = '';
				$cent = ',%';
			}
			elseif( strpos($arFormat['FORMAT'], '.%')!==false ) {
				$valueDec = '';
				$cent = '.%';
			}
		}
		return str_replace(array('#', $cent), array($valueInt, $valueDec), $arFormat['FORMAT']);
	}
}
class OBX_CurrencyFormat extends OBX_DBSimpleStatic {
	static public function getListGroupedByLang($arSort = null) {
		return self::getInstance()->getListGroupedByLang($arSort);
	}
	static public function formatPrice($priceValue, $currencyCode = null, $langID = null, $arFormat = null) {
		return self::getInstance()->formatPrice($priceValue, $currencyCode, $langID, $arFormat);
	}
}
OBX_CurrencyFormat::__initDBSimple(OBX_CurrencyFormatDBS::getInstance());
?>