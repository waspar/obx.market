<?
/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @license Affero GPLv3             **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

$arModuleVersion = array(
	"VERSION" => "1.0.1",
	"VERSION_DATE" => "2013-08-20",
);
return $arModuleVersion;

/**
 * [0.9.3]
 * * Исправлена проблема с невозможностью получения русских строк через ajax на кодировке cp1251
 *
 * [1.0.0]
 * * Стабилизирован релиз
 * * Исправлена проблема с автозагрузкой класса OBX\Market\BasketItemDBS
 *
 * [1.0.1]
 * * Исправлена ошибка в форме редактирования заказа в панели администрирования
 */
