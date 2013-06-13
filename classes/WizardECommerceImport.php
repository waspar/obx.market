<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

namespace OBX\Market\Wizard;

use OBX\Core\Wizard\ImportIBlock;
use OBX\Core\Tools;
use OBX\Market\ECommerceIBlock;
use OBX\Market\OrderProperty;
use OBX\Market\Price;
use OBX\Market\CIBlockPropertyPrice;

class ECommerceImport extends ImportIBlock
{
	protected $_arPrices = array();
	//$this->_arIBlockPricesDependencies[$iblockCode][$ibPricePropCode] = $priceCode;
	protected $_arIBlockPricesDependencies = array();
	protected $_iblockWeightPropID = null;
	protected $_iblockDiscountPropID = null;

	public function __construct($configFilePath) {
		$this->readConfig($configFilePath);
	}

	public function readConfig($configFilePath) {
		if( !file_exists($configFilePath) ) {
			return false;
		}
		$configFileName = basename($configFilePath);
		$configFileDirPath = dirname($configFilePath);
		if( !$this->_bConfigInitialized ) {
			if( !\CModule::IncludeModule('iblock') ) return false;
			if( !\CModule::IncludeModule('obx.core') ) return false;
			if( !\CModule::IncludeModule('obx.market') ) return false;
			__IncludeLang($configFileDirPath.'/lang/'.LANGUAGE_ID.'/'.$configFileName);
			$arRawConfig = require_once $configFilePath;
			if(
				array_key_exists('EXTENDS_IBLOCK_CONFIG', $arRawConfig)
				&& is_file($configFileDirPath.'/'.$arRawConfig['EXTENDS_IBLOCK_CONFIG'])
			) {
				$arRawIBlockConfig = require_once $configFileDirPath.'/'.$arRawConfig['EXTENDS_IBLOCK_CONFIG'];
				$arRawConfig = Tools::arrayMergeRecursiveDistinct($arRawIBlockConfig, $arRawConfig);
			}
			$this->_readIBlockConfig($arRawConfig);
			$this->_readECommConfig($arRawConfig);
			$this->_bConfigInitialized = true;
		}
		return $this->_bConfigInitialized;
	}

	protected function _readECommConfig(&$arRawConfig) {
		$this->_arConfig['ECOMMERCE_IBLOCK'] = array();
		if( array_key_exists('ECOMMERCE_IBLOCK', $arRawConfig) && is_array($arRawConfig['ECOMMERCE_IBLOCK']) ) {
			foreach($arRawConfig['ECOMMERCE_IBLOCK'] as $ecommIBlockCode) {
				if( array_key_exists($ecommIBlockCode, $this->_arConfig['IBLOCK']) ) {
					$this->_arConfig['ECOMMERCE_IBLOCK'][] = $ecommIBlockCode;
				}
			}
		}
		$this->_arConfig['PRICE_LIST'] = array();
		if( array_key_exists('PRICE_LIST', $arRawConfig) && is_array($arRawConfig['PRICE_LIST']) ) {
			foreach($arRawConfig['PRICE_LIST'] as $priceCode => &$arPrice) {
				if( !array_key_exists('CURRENCY', $arPrice) || !is_string($arPrice['CURRENCY'])) {
					continue;
				}
				if( !array_key_exists('NAME', $arPrice) || !is_string($arPrice['NAME'])) {
					continue;
				}
				$arPrice['CURRENCY'] = substr(strtoupper($arPrice['CURRENCY']), 0, 3);
				$this->_arConfig['PRICE_LIST'][$priceCode] = array(
					'ID' => null,
					'CODE' => $priceCode,
					'NAME' => $arPrice['NAME']
				);
				$arCurPriceConfig = &$this->_arConfig['PRICE_LIST'][$priceCode];
				if( array_key_exists('IBLOCK_PROPS', $arPrice) && is_array($arPrice['IBLOCK_PROPS']) ) {
					$arCurPriceConfig['IBLOCK_PROPS'] = array();
					foreach($this->_arConfig['ECOMMERCE_IBLOCK'] as $ecommIBlockCode) {
						if( array_key_exists($ecommIBlockCode, $arCurPriceConfig['IBLOCK_PROPS']) ) {
							$ibPricePropCode = $arCurPriceConfig['IBLOCK_PROPS'][$ecommIBlockCode];
						}
						else {
							$ibPricePropCode = $priceCode;
						}
						$arCurPriceConfig['IBLOCK_PROPS'][$ecommIBlockCode] = $ibPricePropCode;
						if( !array_key_exists($ecommIBlockCode, $this->_arIBlockPricesDependencies) ) {
							$this->_arIBlockPricesDependencies[$ecommIBlockCode] = array();
						}
						$this->_arIBlockPricesDependencies[$ecommIBlockCode][$ibPricePropCode] = $priceCode;
					}
				}
			}
		}
		$this->_arConfig['DISCOUNT_IBLOCK_PROPS'] = array();
		foreach($this->_arConfig['ECOMMERCE_IBLOCK'] as $ecommIBlockCode) {
			if(
				array_key_exists('DISCOUNT_IBLOCK_PROPS', $arRawConfig)
				&& is_array($arRawConfig['DISCOUNT_IBLOCK_PROPS'])
				&& array_key_exists($ecommIBlockCode, $arRawConfig['DISCOUNT_IBLOCK_PROPS'])
			) {
				$ibDiscountPropCode = $arRawConfig['DISCOUNT_IBLOCK_PROPS'][$ecommIBlockCode];
			}
			else {
				$ibDiscountPropCode = 'DISCOUNT';
			}
			$this->_arConfig['DISCOUNT_IBLOCK_PROPS'][$ecommIBlockCode] = $ibDiscountPropCode;
		}

		$this->_arConfig['WEIGHT_IBLOCK_PROPS'] = array();
		foreach($this->_arConfig['ECOMMERCE_IBLOCK'] as $ecommIBlockCode) {
			if(
				array_key_exists('WEIGHT_IBLOCK_PROPS', $arRawConfig)
				&& is_array($arRawConfig['WEIGHT_IBLOCK_PROPS'])
				&& array_key_exists($ecommIBlockCode, $arRawConfig['WEIGHT_IBLOCK_PROPS'])
			) {
				$ibDiscountPropCode = $arRawConfig['WEIGHT_IBLOCK_PROPS'][$ecommIBlockCode];
			}
			else {
				$ibDiscountPropCode = 'WEIGHT';
			}
			$this->_arConfig['WEIGHT_IBLOCK_PROPS'][$ecommIBlockCode] = $ibDiscountPropCode;
		}

		$this->_arConfig['ORDER_PROPS'] = array();
		if( array_key_exists('ORDER_PROPS', $arRawConfig) && is_array($arRawConfig['ORDER_PROPS']) ) {
			foreach($arRawConfig['ORDER_PROPS'] as $orderPropCode => &$arOrderProp) {
				if( !array_key_exists('NAME', $arOrderProp) ) {
					continue;
				}
				if( !array_key_exists('PROPERTY_TYPE', $arOrderProp) ) {
					continue;
				}
				if( !array_key_exists('ACCESS', $arOrderProp) ) {
					continue;
				}
				if(
					$arOrderProp['PROPERTY_TYPE'] == 'L'
					&& (
						!array_key_exists('ENUM_LIST', $arOrderProp)
						|| !is_array($arOrderProp['ENUM_LIST'])
					)
				) {
					continue;
				}
				foreach($arOrderProp['ENUM_LIST'] as $enumCode => &$arEnum) {
					$arEnum['CODE'] = $enumCode;
				}
				$this->_arConfig['ORDER_PROPS'][$orderPropCode] = $arOrderProp;
			}
		}
	}

	public function selectIBlock($iblockCode) {
		parent::selectIBlock($iblockCode);
		if($this->_bIBlockSelected) {
			if( array_key_exists($this->_iblockCode, $this->_arConfig['WEIGHT_IBLOCK_PROPS']) ) {
				$rsWeightIBP = \CIBlockProperty::GetList(array(), array(
					'IBLOCK_ID' => $this->_iblockID,
					'CODE' => $this->_arConfig['WEIGHT_IBLOCK_PROPS'][$this->_iblockCode],
					'PROPERTY_TYPE' => 'N'
				));
				if( $arWeightIBP = $rsWeightIBP->Fetch() ) {
					$this->_iblockWeightPropID = $arWeightIBP['ID'];
				}
			}
			if( array_key_exists($this->_iblockCode, $this->_arConfig['DISCOUNT_IBLOCK_PROPS']) ) {
				$rsDiscountIBP = \CIBlockProperty::GetList(array(), array(
					'IBLOCK_ID' => $this->_iblockID,
					'CODE' => $this->_arConfig['DISCOUNT_IBLOCK_PROPS'][$this->_iblockCode],
					'PROPERTY_TYPE' => 'N'
				));
				if( $arDiscountIBP = $rsDiscountIBP->Fetch() ) {
					$this->_iblockDiscountPropID = $arDiscountIBP['ID'];
				}
			}
		}
		return $this->_bIBlockSelected;
	}

	protected function __getPriceID($priceCode) {
		if( !array_key_exists($priceCode, $this->_arConfig['PRICE_LIST']) ) {
			return 0;
		}
		$priceID = 0;
		$arPrice = Price::getListArray(null, array('CODE' => $priceCode));
		if( empty($arPrice) ) {
			$priceID = Price::add(array(
				'CODE' => $priceCode,
				'NAME' => $this->_arConfig['PRICE_LIST'][$priceCode]['NAME'],
				'CURRENCY' => $this->_arConfig['PRICE_LIST'][$priceCode]['CURRENCY'],
				'SORT' => $this->_arConfig['PRICE_LIST'][$priceCode]['SORT'],
			));
		}
		else {
			$priceID = $arPrice[0]['ID'];
		}
		return $priceID;
	}

	public function createPrices() {
		foreach($this->_arConfig['PRICE_LIST'] as $priceCode => $arPriceConfig) {
			$this->__getPriceID($priceCode);
		}
	}

	public function createIBlockPriceProps() {
		if( ! $this->_bIBlockSelected ) return false;
		if( $this->_iblockID <= 0 ) return false;

		if( !array_key_exists($this->_iblockCode, $this->_arIBlockPricesDependencies) ) {
			return false;
		}
		$bSuccess = true;
		foreach($this->_arIBlockPricesDependencies[$this->_iblockCode] as $ibPricePropCode => $priceCode) {
			$priceID = $this->__getPriceID($priceCode);
			if($priceID<=0) {
				continue;
			}
			$rsIBPriceProp = \CIBlockProperty::GetList(array(), array(
				'IBLOCK_ID' => $this->_iblockID,
				'CODE' => $ibPricePropCode,
				'PROPERTY_TYPE' => 'N'
			));
			if( $arIBPriceProp = $rsIBPriceProp->Fetch() ) {
				$bSuccess = CIBlockPropertyPrice::add(array(
					'PRICE_ID' => $priceID,
					'IBLOCK_ID' => $this->_iblockID,
					'IBLOCK_PROP_ID' => $arIBPriceProp['ID']
				));
				$bSuccess = ($bSuccess)?true:false;
				if(!$bSuccess) {
					$arError = CIBlockPropertyPrice::popLastError('ARRAY');
					if($arError['CODE'] == '4') $bSuccess = true;
				}
			}
			else {
				$bSuccess = false;
			}
		}
		return $bSuccess;
	}

	public function installECommerceIBlockData() {
		if( ! $this->_bIBlockSelected ) return false;
		if( $this->_iblockID <= 0 ) return false;
		$arECommExistIB = ECommerceIBlock::getByID($this->_iblockID);
		$arECommIBFields = array(
			'IBLOCK_ID' => $this->_iblockID,
			'WEIGHT_VAL_PROP_ID' => $this->_iblockWeightPropID,
			'DISCOUNT_VAL_PROP_ID' => $this->_iblockDiscountPropID
		);
		if( empty($arECommExistIB) ) {
			ECommerceIBlock::add($arECommIBFields);
		}
		else {
			ECommerceIBlock::update($arECommIBFields);
		}
		$this->createIBlockPriceProps();
	}

	public function installOrderProperties() {
		foreach($this->_arConfig['ORDER_PROPS'] as $propCode => $arPropFields) {
			$arPropFields['CODE'] = $propCode;
			$rsPropList = OrderProperty::getList(null, array('CODE' => $propCode));
			if( $arExistProp = $rsPropList->Fetch() ) {
				OrderProperty::update($arPropFields);
			}
			else {
				OrderProperty::add($arPropFields);
			}
		}
	}
}
///////////// Пример конфигурационного файла
//	$arECommerceInstallerConfig = array(
//		'EXTENDS_IBLOCK_CONFIG' => '../iblock/.config.php',
//
//		// TODO: [pronix:2013-06-12] Это пока не реализуем. Все нужные валюты есть при установке obx.market
//		'CURRENCY' => array(),
//
//		// в будущем формат данного массива изменится
//		'ECOMMERCE_IBLOCK' => array(
//			'cig', 'fluid', 'kit', 'accessories'
//		),
//
//		'PRICE_LIST' => array(
//			'PRICE' => array(
//				'CURRENCY' => 'RUB',
//				'NAME' => GetMessage('OBX_MARKET_WIZ_PRICE_PRICE_NAME'),
//				'SORT' => '10',
//				'PERMISSONS' => array(
//					// Группы имеющие доступ на чтение цен
//					'1', '2'
//				),
//				'IBLOCK_PROPS' => array(
//					// $IBLOCK_CODE => $PROPERTY_CODE
//					// если не указано null, будет сделана попытка всзять код цены
//					// и по этому коду найти соответствующее свойство
//					'cig'			=> 'PRICE',
//					'fluid'			=> 'PRICE',
//					'kit'			=> 'PRICE',
//					'accessories'	=> 'PRICE',
//				),
//			),
//			'WHOLESALE' => array(
//				'CURRENCY' => 'RUB',
//				'NAME' => GetMessage('OBX_MARKET_WIZ_PRICE_WHOLESALE_NAME'),
//				'SORT' => '20',
//				'PERMISSIONS' => array(
//					'1', '2'
//				),
//				'IBLOCK_PROPS' => array(
//					'cig'			=> 'WHOLESALE',
//					'fluid'			=> 'WHOLESALE',
//					'kit'			=> 'WHOLESALE',
//					'accessories'	=> 'WHOLESALE',
//				)
//			),
//		),
//		// TODO: [pronix:2013-06-12] В дальнейшем когда в API obx.market появится множественные скидки будем массив DISCOUNT_LIST
//		// и задаватьсябудет примерно примерно так же как PRICE_LIST
//		// пока один массив с привязкой одной скидки к каждому элементу
//		// Если для инфоблока не задано явно, то будет сделана попытка получить свойство с кодом 'DISCOUNT'
//		'DISCOUNT_IBLOCK_PROPS' => array(
//			'cig'			=> 'DISCOUNT',
//			'fluid'			=> 'DISCOUNT',
//			'kit'			=> 'DISCOUNT',
//			'accessories'	=> 'DISCOUNT',
//		),
//
//		// Если для инфоблока не задано явно, то будет сделана попытка получить свойство с кодом 'WEIGHT'
//		'WEIGHT_IBLOCK_PROPS' => array(
//			'cig'			=> 'WEIGHT',
//			'fluid'			=> 'WEIGHT',
//			'kit'			=> 'WEIGHT',
//			'accessories'	=> 'WEIGHT',
//		),
//
//		// Массив свойств заказов
//		'ORDER_PROPS' => array(
//			// В качестве ключа выступает поле CODE свойства
//			'PHONE_NUMBER' => array(
//				'NAME' => GetMessage('OBX_MARKET_WIZ_ORDER_PROP_PHONE_NUMBER_NAME'),
//				'DESCRIPTION' => GetMessage('OBX_MARKET_WIZ_ORDER_PROP_PHONE_NUMBER_DESCR'),
//				'SORT' => 100,
//				'PROPERTY_TYPE' => 'S',
//				'ACTIVE' => 'Y',
//				'IS_SYS' => 'Y',
//				'ACCESS' => 'W',
//				'IS_SYS' => 'Y',
//				OBX_MAGIC_WORD => 'Y'
//			),
//
//			// Пример для типа список
//			//'DELIVERY' => array(
//			//	'NAME' => GetMessage('OBX_MARKET_WIZ_ORDER_PROP_DELIVERY_NAME'),
//			//	'DESCRIPTION' => GetMessage('OBX_MARKET_WIZ_ORDER_PROP_DELIVERY_DESCR'),
//			//	'SORT' => 100,
//			//	'PROPERTY_TYPE' => 'L',
//			//	'ACTIVE' => 'Y',
//			//	'ACCESS' => 'W',
//			//	'IS_SYS' => 'Y',
//			//	OBX_MAGIC_WORD => 'Y',
//			//	'ENUM_LIST' => array(
//			//		// в качестве ключа выступает поле CODE списочного значения свойства заказа
//			//		'1' => array(
//			//			'VALUE' => GetMessage('OBX_MARKET_WIZ_ORDER_PROP_DELIVERY_ENUM_1'),
//			//			'SORT' => 10,
//			//		),
//			//		'2' => array(
//			//			'VALUE' => GetMessage('OBX_MARKET_WIZ_ORDER_PROP_DELIVERY_ENUM_2'),
//			//			'SORT' => 10,
//			//		),
//			//	),
//			//)
//
//		),
//
//		// Массив статусов заказов
//		// TODO: [pronix:2013-06-12] Не обрабатывается, пока достаточно тех что есть устанавливаются с obx.market
//		'ORDER_STATUS_LIST' => array(
//			// В качестве ключа выступает поле CODE статуса
//			'ACCEPTED' => array(
//				'NAME' => GetMessage('OBX_MARKET_WIZ_ORDER_STATUS_ACCEPTED_NAME'),
//				'SORT' => '10',
//				'IS_SYS' => 'Y',
//				OBX_MAGIC_WORD => 'Y'
//			)
//		),
//	);
//	return $arECommerceInstallerConfig;