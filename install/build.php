#!/usr/bin/env php
<?php
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license GPLv3                            **
 ** @mailto rootfavell@gmail.com              **
 ** @mailto tashiro@yandex.ru                 **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

require dirname(__FILE__)."/../../obx.core/classes/Build.php";
$build = new OBX_Build("obx.market");
$build->generateInstallCode();
$build->generateUnInstallCode();
$build->generateBackInstallCode();
$build->backInstallResources();
$build->generateMD5FilesList();
