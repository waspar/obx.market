<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/
namespace {
	define('DBPersistent', true);
	$curDir = dirname(__FILE__);
	$wwwRootStrPos = strpos($curDir, '/bitrix/modules/obx.sms');
	if( $wwwRootStrPos === false ) {
		die('Can\'t find www-root');
	}

	$_SERVER['DOCUMENT_ROOT'] = substr($curDir, 0, $wwwRootStrPos);
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
	__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/__bootstrap_tests.php');
	global $USER;
	global $DB;
	// Без этого фикса почему-то не работает. Не видит это значение в include.php модуля
	global $DBType;
	$DBType = strtolower($DB->type);

	$USER->Authorize(1);
	if( !CModule::IncludeModule('iblock') ) {
		echo('Warning: Module iblock not installed!!!');
	}

	if( !CModule::IncludeModule('obx.sms') ) {
		echo('Warning: Module OBX:SMS not installed!!!');
	}
}

namespace OBX\Sms\Test {
	use OBX\Core\Test\TestCase as CoreTestCase;

	class SmsTestCase extends CoreTestCase {
		public function getCurDir() {
			return dirname(__FILE__);
		}
	}
	SmsTestCase::includeLang(__FILE__);
}

