#!/usr/bin/env php
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

require dirname(__FILE__)."/../../obx.core/classes/OBX_Build.php";
$build = new OBX_Build("obx.market");
$build->generateInstallCode();
$build->generateUnInstallCode();
$build->generateBackInstallCode();
$build->backInstallResources();
$build->generateMD5FilesList();
?>