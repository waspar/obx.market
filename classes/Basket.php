<?
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

class OBX_Basket extends OBX_CMessagePool {

	// Код цены
	const pricePropertyCodeDefault = 'PRICE';
	const defaultBasketID = 'DEF';
	static protected $pricePropertyCode = null;
	static protected $_instances = array();

	protected $_basketID = self::defaultBasketID;
	protected $_arErrors = array();
	protected $_lastError = '';

	const ERR_WRONG_BASKET_ID = 1;

	/**
	 * @param $basketID
	 * @return null|OBX_Basket
	 */
	static public function getInstance($basketID = self::defaultBasketID, &$arError = array()) {
		$regBasketIDValid = '~^[a-zA-Z0-9\_\-\.]{1,10}$~';
		if( !preg_match($regBasketIDValid, $basketID) ) {
			$arError = array(
				'TEXT' => GetMessage('OBX_BASKET_WRONG_BASKET_ID'),
				self::ERR_WRONG_BASKET_ID);
			return null;
		}
		if( !array_key_exists($basketID, self::$_instances) ) {
			self::$_instances[$basketID] = new self($basketID);
		}
		return self::$_instances[$basketID];
	}

	protected function __construct($basketID = self::defaultBasketID) {
		$this->_basketID = $basketID;
		if( !isset($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST']) ) {
			$this->clearBasket();
		}
	}
	final protected function __clone() {}

	// Получить общую стоимость корзины
	public function getBasketCost(){
		$intBasketCost = 0;
		if( !$this->isEmpty() ) {
			foreach($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'] as $productID => $intElementCount) {
				$arSessionElement = &$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$productID];
				$arOptimalPrice = $arSessionElement['PRICE_LIST'][$arSessionElement['OPTIMAL_PRICE']];
				$intBasketCost += $arOptimalPrice['TOTAL_VALUE'] * $intElementCount;
			}
		}
		return $intBasketCost;
	}

	// Проверить корзину или наличие товара
	public function isEmpty($productID = null){
		// Проверка определенного товара
		if($productID){
			$productID = intval($productID);
			if(
				isset($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID])
				&& $_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID]>0
			) {
				return false;
			}
			return true;
		}
		if( isset($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST']) ) {
			return false;
		}
		return true;
	}

	// Очистить корзину
	public function clearBasket() {
		$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'] = array();
		$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'] = array();
	}


	// Получить список всех продуктов.
	public function getProductsList(){
		return $_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'];
	}

	// Получить стоимость определенного товара
	public function getProductCost($productID){
		$intProductCost = 0;
		if(!$this->isEmpty($productID)){
			$intElementCount = $_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID];
			$arElement = &$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$productID];
			if($arElement['PRICE_LIST'][$arElement['OPTIMAL_PRICE']]){
				$intProductCost += round(
					($arElement['PRICE_LIST'][$arElement['OPTIMAL_PRICE']]['TOTAL_VALUE'] * $intElementCount)
					, 5
				);
			}
		}
		return $intProductCost;
	}


	// Получить цену продукта
	public function getProductPrice($productID){
		$intProductPrice = 0;
		if( !$this->isEmpty($productID) ) {
			$arSessionElement = &$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$productID];
			$intProductPrice = $arSessionElement['PRICE_LIST'][$arSessionElement['OPTIMAL_PRICE']]['TOTAL_VALUE'];
		}
		return $intProductPrice;
	}

	//  Получить число продуктов в корзине
	public function getProductsCount(){
		return count($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST']);
	}

	// Получить количество единиц данного продукта
	public function getProductItemsCount($productID){
		if( !$this->isEmpty($productID) ) {
			return $_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID];
		}
		else {
			return null;
		}
	}

	// Удалить товар из корзины
	public function removeProduct($productID){
		if(!$this->isEmpty($productID)){
			unset($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID]);
			unset($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$productID]);
			return true;
		}else return false;
	}


	// Добавить в корзину
	public function addItem($productID, $quantity = 1){
		$productID = intval($productID);
		if(@isset($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID])){
			// Увеличение количества
			return $this->changeItemCount($productID, $quantity);
		}
		$rsElement = CIBlockElement::GetByID($productID);
		if($obElement = $rsElement->GetNextElement()){
			$arElement = $obElement->GetFields();
			$arPriceList = OBX_Price::getProductPriceList($arElement["ID"]);
			$arElement['OPTIMAL_PRICE'] = null;
			$arElement['PROPERTIES'] = $obElement->GetProperties();

			$bFoundAvailablePrices = false;
			$arElement['PRICE_LIST'] = array();
			if( count($arPriceList)>0 ) {
				foreach($arPriceList as &$arPrice) {
					if($arPrice['AVAILABLE'] == 'Y') {
						$bFoundAvailablePrices = true;
					}
					$arElement['PRICE_LIST'][$arPrice['PRICE_CODE']] = $arPrice;
					if($arPrice['IS_OPTIMAL'] == 'Y') {
						$arElement['OPTIMAL_PRICE'] = $arPrice['PRICE_CODE'];
					}
				}
			}
			if(!$bFoundAvailablePrices) {
				$this->addError(GetMessage('OBX_BASKET_ERROR_1', array(
					'#ID#' => $arElement['ID'],
					'#NAME#' => $arElement['NAME']
				)), 1);
				return false;
			}
			if( $arElement['OPTIMAL_PRICE'] == null ) {
				$this->addError(GetMessage('OBX_BASKET_ERROR_2', array(
					'#ID#' => $arElement['ID'],
					'#NAME#' => $arElement['NAME']
				)), 2);
				return false;
			}

			// Добавление товара в список с количествами
			$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$arElement['ID']] = $quantity;

			if( !is_array($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$arElement['ID']]) ) {
				$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$arElement['ID']] = array();
			}
			$arSessionElement = &$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.PRODUCTS'][$arElement['ID']];
			if( count($arSessionElement)<1 ){
				// Добавление в список прдуктов
				$arSessionElement['ID'] = $arElement['ID'];
				$arSessionElement['IBLOCK_ID'] = $arElement['IBLOCK_ID'];
				$arSessionElement['NAME'] = $arElement['NAME'];
				$arSessionElement['DETAIL_PAGE_URL'] = $arElement['DETAIL_PAGE_URL'];
				$arSessionElement['IBLOCK_SECTION_ID'] = $arElement['IBLOCK_SECTION_ID'];
				$arSessionElement['PRICE_LIST'] = $arElement['PRICE_LIST'];
				$arSessionElement['OPTIMAL_PRICE'] = $arElement['OPTIMAL_PRICE'];
				foreach ($arElement['PROPERTIES'] as $code=>$arrayProp){
					$arSessionElement['PROPERTIES'][$code]['ID'] = $arElement['PROPERTIES'][$code]['ID'];
					$arSessionElement['PROPERTIES'][$code]['NAME'] = $arElement['PROPERTIES'][$code]['NAME'];
					$arSessionElement['PROPERTIES'][$code]['VALUE'] = $arElement['PROPERTIES'][$code]['VALUE'];
				}
				return true;
			}
			else{
				return true;
			}
		}
		return false;
	}


	// Получить список единиц
	public function getItemsList(){
	   return $_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'];
	}

	// Получить число единиц товара в корзине
	public function getItemsCount(){
		$countItems = 0;
		if(!$this->isEmpty())
		foreach($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'] as $itemCount){
			$countItems += $itemCount;
		}
		return $countItems;
	}

	// Прибавить (отнять) к количеству определенного товара разницу (delta)
	public function changeItemCount($productID, $delta = 1){
		if(!$this->isEmpty($productID)){
			$_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID]+=intval($delta);
			if($_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID]<1){
				$this->removeProduct($productID);
			}
			return true;
		}
		return false;
	}

	// Установить определенное количество единиц товара в корзине
	public function setItemCount($productID, $quantity = null){
		if(!$this->isEmpty($productID) and $quantity){
			if($quantity==0){
				$this->removeProduct($productID);
			}else $_SESSION['A68.MARKET.BASKET.'.$this->_basketID.'.LIST'][$productID]=intval($quantity);
			return true;
		}else return false;
	}

	public function getBasketID(){
		return $this->_basketID;
	}
}
?>