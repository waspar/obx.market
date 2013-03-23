<?php
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Artem P. Morozov          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

IncludeModuleLangFile(__FILE__);

class OBX_CIBlockPropertyPriceList extends CDBResult implements Iterator
{
	// Переменные объекта
	protected $_CDBResult;
	public $curentOrder = false;
	private $position = 0;

	// Метод итератора (переход на начало)
	function rewind() {
		$this->position = 0;
		$this->curentOrder = $this->_CIBlockOrdersListResult->GetNext();
	}
	// Метод итератора (текущий элемент)
	function current() {
		return $this->curentOrder;
	}
	// Метод итератора (текущий ключ)
	function key() {
		return $this->position;
	}
	// Метод итератора (следующий элемент)
	function next() {
		++$this->position;
		$this->curentOrder = $this->_CIBlockOrdersListResult->GetNext();
	}
	// Метод итератора (проверка)
	function valid() {
		return is_array($this->curentOrder);
	}


	// Конструктор - содаем список на базе выборки из ИБ
	protected function __construct(CDBResult &$_CDBResult){
		$this->position = 0;
		$this->_CDBResult = $_CDBResult;
		parent::__construct($this->_CDBResult);
	}
	final protected function __clone() {}

	/**
	 * @return OBX_CIBPrice
	 */
	public function getNextPrice() {
		// TODO: Дописать механизм получения дополнительных данных о цене
	}
}
class OBX_CIBEPrice {
	// TODO: Дописать механизм получения дополнительных данных о цене
}
?>