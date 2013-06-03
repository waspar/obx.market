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
use OBX\Core\CMessagePoolStatic;
use OBX\Core\DBSimple;
use OBX\Core\DBSimpleStatic;
use OBX\Core\DBSResult;

use OBX\Market\Price;
use OBX\Market\PriceDBS;
use OBX\Market\ECommerceIBlock;

IncludeModuleLangFile(__FILE__);

class CIBlockPropertyPriceDBS extends DBSimple
{
	protected $_arTableDefaultFields = array();
	protected $_arTableList = array(
		'P'		=> 'obx_price',
		'IB'	=> 'b_iblock',
		'IBP'	=> 'b_iblock_property',
		'L'		=> 'obx_price_ibp',
		'EIB'	=> 'obx_ecom_iblock',
	);
	protected $_mainTable = 'L';
	public $_arTableLinks = array(
		0 => array(
			array('L' => 'PRICE_ID'),
			array('P' => 'ID')
		),
		1 => array(
			array('L'	=> 'IBLOCK_PROP_ID'),
			array('IBP'	=> 'ID')
		),
		2 => array(
			array('L'	=> 'IBLOCK_ID'),
			array('IB'	=> 'ID')
		),
		3 => array(
			array('L'	=> 'IBLOCK_ID'),
			array('EIB' => 'IBLOCK_ID')
		)
	);
	protected $_arTableFields = array(
		//'ID'					=> array('L'	=> 'ID'),
		'PRICE_ID'				=> array('L'	=> 'PRICE_ID'),
		'PRICE_NAME'			=> array('P'	=> 'NAME'),
		'PRICE_CODE'			=> array('P'	=> 'CODE'),
		'CURRENCY'				=> array('P'	=> 'CURRENCY'),
		'IBLOCK_ID'				=> array('L'	=> 'IBLOCK_ID'),
		'IBLOCK_CODE'			=> array('IB'	=> 'CODE'),
		'IBLOCK_NAME'			=> array('IB'	=> 'NAME'),
		'IBLOCK_PROP_ID'		=> array('L'	=> 'IBLOCK_PROP_ID'),
		'IBLOCK_PROP_CODE'		=> array('IBP'	=> 'CODE'),
		'IBLOCK_PROP_NAME'		=> array('IBP'	=> 'NAME'),
		'IBLOCK_PROP_ACTIVE'	=> array('IBP'	=> 'ACTIVE'),
		'IBLOCK_PROP_REQUIRED'	=> array('IBP'	=> 'IS_REQUIRED'),
		'IBLOCK_ECOM_ID'		=> array('EIB'	=> 'IBLOCK_ID'),
	);
	protected $_arTableUnique = array(
		'obx_price_ibpr' => array('IBLOCK_ID', 'PRICE_ID'),
		'obx_price_ibpp' => array('IBLOCK_ID', 'IBLOCK_PROP_ID')
	);
	protected $_arTableLeftJoin = array(
		'EIB' => 'L.IBLOCK_ID = EIB.IBLOCK_ID'
	);

	protected $_mainTablePrimaryKey = null;
	protected $_mainTableAutoIncrement = null;
//	protected $_arSelectDefault = array(
//		'ID',
//		'PRICE_ID',
//		'PRICE_CODE',
//		'PRICE_NAME',
//		'IBLOCK_ID',
//		'IBLOCK_CODE',
//		'IBLOCK_NAME',
//		'IBLOCK_PROP_ID',
//		'IBLOCK_PROP_CODE',
//		'IBLOCK_PROP_NAME'
//	);
	protected $_arSortDefault = array(
		'ID' => 'ASC'
	);
	protected $_arDBSimpleLangMessages = array();
	protected $_arTableFieldsCheck = array();
	public function __check_PRICE_ID(&$fieldValue, &$arCheckData) {
		$fieldValue = intval($fieldValue);
		$rsPrice = Price::getByID($fieldValue, null, true);
		if( ($arPrice = $rsPrice->Fetch()) ) {
			$arCheckData = $arPrice;
			return true;
		}
		return false;
	}
	protected function __check_IBLOCK_ID(&$fieldValue, &$arCheckData) {
		$arCommenctIBlock = ECommerceIBlock::getByID($fieldValue);
		if( !empty($arCommenctIBlock) ) {
			$arCheckData = $arCommenctIBlock;
			return true;
		}
		$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_6'), 6);
		return false;
	}
	public function __construct() {
		$this->_arTableFieldsCheck = array(
			//'ID'				=> self::FLD_T_INT | self::FLD_NOT_NULL,
			'PRICE_ID'			=> self::FLD_T_INT
									| self::FLD_NOT_NULL
									| self::FLD_NOT_ZERO
									| self::FLD_REQUIRED
									| self::FLD_CUSTOM_CK,

			'IBLOCK_ID'			=> self::FLD_T_IBLOCK_ID
									| self::FLD_NOT_NULL
									| self::FLD_NOT_ZERO
									| self::FLD_REQUIRED
									| self::FLD_CUSTOM_CK
									| self::FLD_BRK_INCORR,

			'IBLOCK_PROP_ID'	=> self::FLD_T_IBLOCK_PROP_ID
									| self::FLD_NOT_NULL
									| self::FLD_REQUIRED,
		);
		$this->_arDBSimpleLangMessages = array(
			'REQ_FLD_IBLOCK_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_1'),
				'CODE' => 1
			),
			'REQ_FLD_IBLOCK_PROP_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_2'),
				'CODE' => 2
			),
			'REQ_FLD_PRICE_ID' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_3'),
				'CODE' => 3
			),
			'DUP_ADD_obx_price_ibpr' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_4'),
				'CODE' => 4
			),
			'DUP_UPD_obx_price_ibpr' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_7'),
				'CODE' => 7
			),
			'DUP_ADD_obx_price_ibpp' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_10'),
				'CODE' => 10
			),
			'DUP_UPD_obx_price_ibpp' => array(
				'TYPE' => 'E',
				'TEXT' => GetMessage('OBX_MARKET_PRICE_PROP_ERROR_11'),
				'CODE' => 11
			),
		);
	}

	/**
	 * Возвращает полный список цен для каталога(ов)
	 * @param int $IBLOCK_ID
	 * @param bool $bResultCDBResult
	 * @return array|bool|\CDBResult
	 */
	public function getFullPriceList($IBLOCK_ID = 0, $bResultCDBResult = false) {
		global $DB;
		$IBLOCK_ID = intval($IBLOCK_ID);
		$sqlList = <<<SQL
		SELECT
			 IB.ID AS IBLOCK_ID
			,IB.CODE AS IBLOCK_CODE
			,IB.NAME AS IBLOCK_NAME
			,(SELECT IF(
						(SELECT IBLOCK_ID FROM obx_ecom_iblock AS EB WHERE EB.IBLOCK_ID = IB.ID
						) IS NULL
						,'N'
						,'Y'
					)
				) AS IBLOCK_IS_ECOM
			,PR.ID AS PRICE_ID
			,PR.CODE AS PRICE_CODE
			,PR.NAME AS PRICE_NAME
			,PR.SORT AS PRICE_SORT
			,PR.CURRENCY AS CURRENCY
			,BP.ID AS PROPERTY_ID
			,BP.CODE AS PROPERTY_CODE
			,BP.NAME AS PROPERTY_NAME
			,BP.PROPERTY_TYPE AS PROPERTY_TYPE
		FROM
			obx_price AS PR
		LEFT JOIN
			b_iblock AS IB ON (true)
		LEFT JOIN
			obx_price_ibp AS LP ON (LP.IBLOCK_ID = IB.ID AND LP.PRICE_ID = PR.ID)
		LEFT JOIN
			b_iblock_property AS BP ON (BP.IBLOCK_ID = LP.IBLOCK_ID AND BP.ID = LP.IBLOCK_PROP_ID)
SQL;

		if($IBLOCK_ID>0) {
			$sqlList .= ' WHERE IB.ID = '.$IBLOCK_ID;
		}
		$sqlList .= ' ORDER BY IB.ID ASC, PR.ID ASC';
		$res = $DB->Query($sqlList, false, 'File: '.__FILE__."<br />\nLine: ".__LINE__);
		$res = new DBSResult($res);
		$res->setAbstractionName(get_called_class());
		if($bResultCDBResult) {
			return $res;
		}
		$arList = array();
		while( ($arItem = $res->Fetch()) ) {
			$arList[] = $arItem;
		}
		return $arList;
	}

	/**
	 * Возвращает список свойств каталога(ов)/инфоблока(ов), которые связаны с ценами
	 * примечение:
	 * для цен, для которых нет связок со свойствами элементов
	 * не вернет строк, в отличие от getFullPriceList, которые вернет строки с null
	 * @param int $IBLOCK_ID
	 * @param bool $bResultCDBResult
	 * @return array|bool|DBSResult
	 */
	public function getFullPropList($IBLOCK_ID = 0, $bResultCDBResult = false) {
		global $DB;
		$IBLOCK_ID = intval($IBLOCK_ID);
		$sqlList = <<<SQL
		SELECT
			 P.ID AS PROPERTY_ID
			,P.CODE AS PROPERTY_CODE
			,P.NAME AS PROPERTY_NAME
			,P.PROPERTY_TYPE AS PROPERTY_TYPE
			,P.IBLOCK_ID AS IBLOCK_ID
			,B.CODE AS IBLOCK_CODE
			,B.IBLOCK_TYPE_ID AS IBLOCK_TYPE_ID
			,(SELECT IF(C.IBLOCK_ID IS NULL, 'N', 'Y') ) AS IBLOCK_IS_ECOM
			,L.PRICE_ID AS PRICE_ID
			,R.CODE AS PRICE_CODE
			,R.NAME AS PRICE_NAME
			,R.CURRENCY AS CURRENCY
			,C.WEIGHT_VAL_PROP_ID as WEIGHT_VAL_PROP_ID
			,C.DISCOUNT_VAL_PROP_ID as DISCOUNT_VAL_PROP_ID
		FROM
			 b_iblock AS B
			,b_iblock_property AS P

		LEFT JOIN
			obx_ecom_iblock AS C ON (P.IBLOCK_ID = C.IBLOCK_ID)
		LEFT JOIN
			obx_price_ibp AS L ON (L.IBLOCK_ID = P.IBLOCK_ID AND L.IBLOCK_PROP_ID = P.ID)
		LEFT JOIN
			obx_price AS R ON (L.PRICE_ID = R.ID)
		WHERE
				B.ID = P.IBLOCK_ID
			AND P.PROPERTY_TYPE = 'N'
SQL;
		if($IBLOCK_ID>0) {
			$sqlList .= ' AND B.ID = '.$IBLOCK_ID;
		}
		$res = $DB->Query($sqlList, false, 'File: '.__FILE__."<br />\nLine: ".__LINE__);
		$res = new DBSResult($res);
		$res->setAbstractionName(get_called_class());
		if($bResultCDBResult) {
			return $res;
		}
		$arList = array();
		while( ($arItem = $res->Fetch()) ) {
			$arList[] = $arItem;
		}
		return $arList;
	}

	public function addIBlockPriceProperty($arFields) {
		$arFieldsPrepared = array(
			'PRICE_ID' => null,
			'PRICE_CODE' => null,
			'IBLOCK_ID' => null,
			'NAME' => null,
			'CODE' => null,
			'SORT' => 500,
			'PROPERTY_TYPE' => 'N',
			'ACTIVE' => 'Y'
		);
		
		foreach($arFieldsPrepared as $fieldName => &$fieldValue) {
			$bPriceIDAlreadySet = false;
			if($fieldName=='PRICE_ID') {
				$fieldValue = intval($arFields['PRICE_ID']);
				$arPrice = Price::getByID($fieldValue);
				if( empty($arPrice) ) {
					$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_3'), 3);
					return 0;
				}
				unset($arFieldsPrepared['PRICE_CODE']);
				$bPriceIDAlreadySet = true;
				continue;
			}
			if($fieldName=='PRICE_CODE') {
				if(!$bPriceIDAlreadySet) {
					$fieldValue = trim($arFields['PRICE_CODE']);
					if( strlen($fieldValue)<1 ) {
						$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_3'), 3);
						return 0;
					}
					$arPrice = Price::getByCode($fieldValue, null);
					if( empty($arPrice) ) {
						$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_3'), 3);
						return 0;
					}
				}
				continue;
			}
			if($fieldName=='IBLOCK_ID') {
				$fieldValue = intval($arFields['IBLOCK_ID']);
				if(!$fieldValue) {
					$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_1'), 1);
					return 0;
				}
				$arCommerceIBlock = ECommerceIBlock::getByID($fieldValue);
				if( empty($arCommerceIBlock) ) {
					$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_6'), 6);
					return 0;
				}
				continue;
			}
			if($fieldName=='NAME') {
				$fieldValue = htmlspecialcharsEx($arFields['NAME']);
				if( strlen($fieldValue)<1 ) {
					$fieldValue = $arPrice['NAME'];
				}
				continue;
			}
			if($fieldName=='CODE') {
				$fieldValue = trim($arFields['CODE']);
				if( strlen($arPrice)<1 ) {
					$fieldValue = $arPrice['CODE'];
				}
				elseif( !preg_match('~^[a-zA-Z\_][a-z0-9A-Z\_]{0,15}$~', $fieldValue) ) {
					$this->addError(GetMessage('OBX_MARKET_PRICE_PROP_ERROR_9'), 9);
					return false;
				}
				continue;
			}
			if($fieldName=='SORT') {
				$fieldValue = intval($arFields['SORT']);
				continue;
			}
			
			$IBProp = new \CIBlockProperty;
			$newID = $IBProp->Add($arFieldsPrepared);
			if(!$newID) {
				$this->addError($IBProp->LAST_ERROR);
				return 0;
			}
			$newPricePropID = $this->add(array(
				'IBLOCK_ID' => $arFields['IBLOCK_ID'],
				'IBLOCK_PROP_ID' => $newID,
				'PRICE_ID' => $arPrice['ID']
			));
			return $newPricePropID;
		}
	}
	
	protected $_bDeleteIBlockPropOnDeletePrice = false;
	public function delete($ID, $bDeleteIBlockProp = false) {
		$this->_bDeleteIBlockPropOnDeletePrice = ($bDeleteIBlockProp)?true:false;
		$bSuccess = parent::delete($ID);
		$this->_bDeleteIBlockPropOnDeletePrice = false;
		return $bSuccess;
	}

	protected function _onAfterDelete(&$arExists) {
		if( $this->_bDeleteIBlockPropOnDeletePrice ) {
				return \CIBlockProperty::Delete($arExists['IBLOCK_PROP_ID']);
		}
		return true;
	}

	static public function onIBlockPropertyDelete($ID) {
		$that = self::getInstance();
		$that->deleteByFilter(array('IBLOCK_PROP_ID' => $ID));
	}
	static public function onIBlockDelete($ID) {
		$that = self::getInstance();
		$that->deleteByFilter(array('IBLOCK_ID' => $ID));
	}

	public function getValue($IBLOCK_ID, $PRICE_ID, $bFormat = true) {

	}

	public function registerModuleDependencies() {
		RegisterModuleDependences(
				'iblock', 'OnBeforeIBlockPropertyDelete',
				'obx.market', __CLASS__, 'onIBlockPropertyDelete', 510);
		RegisterModuleDependences(
				'iblock', 'OnIBlockDelete',
				'obx.market', __CLASS__, 'onIBlockDelete', 520);
	}
	public function unRegisterModuleDependencies() {
		UnRegisterModuleDependences(
				'iblock', 'OnBeforeIBlockPropertyDelete',
				'obx.market', __CLASS__, 'onIBlockPropertyDelete');
		UnRegisterModuleDependences(
				'iblock', 'OnIBlockDelete',
				'obx.market', __CLASS__, 'onIBlockDelete');
	}
}


class CIBlockPropertyPrice extends DBSimpleStatic {
	static public function delete($ID, $bDeleteIBlockProp = false) {
		return self::getInstance()->delete($ID, $bDeleteIBlockProp);
	}
	static public function getFullPriceList($IBLOCK_ID = 0, $bResultCDBResult = false) {
		return self::getInstance()->getFullPriceList($IBLOCK_ID, $bResultCDBResult);
	}
	static public function getFullPropList($IBLOCK_ID = 0, $bResultCDBResult = false) {
		return self::getInstance()->getFullPropList($IBLOCK_ID, $bResultCDBResult);
	}
	static public function addIBlockPriceProperty($arFields) {
		return self::getInstance()->addIBlockPriceProperty($arFields);
	}

	public function onIBlockPropertyDelete($ID) {
		return self::getInstance()->onIBlockPropertyDelete($ID);
	}
	static public function onIBlockDelete($ID) {
		return self::getInstance()->onIBlockDelete($ID);
	}
	public function getValue($IBLOCK_ID, $PRICE_ID, $bFormat = true) {
		return self::getInstance()->getValue($IBLOCK_ID, $PRICE_ID, $bFormat);
	}

	static public function registerModuleDependencies() {
		return self::getInstance()->registerModuleDependencies();
	}
	static public function unRegisterModuleDependencies() {
		return self::getInstance()->unRegisterModuleDependencies();
	}
}
CIBlockPropertyPrice::__initDBSimple(CIBlockPropertyPriceDBS::getInstance());
