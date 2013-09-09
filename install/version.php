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
	"VERSION" => "1.0.2",
	"VERSION_DATE" => "2013-09-09",
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
 * * В obx.basket.js все языковые сообщения вынесены в кофигурационный объект msg
 *
 * [1.0.2]
 *  * Добавлены иконки модуля для пунктов меню
 *  * Обновлен подмодуль obx.core до версии 1.1.0
 */
