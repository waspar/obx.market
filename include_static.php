<?php
require $_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/obx.core/include_static.php';
$currentDir = dirname(__FILE__);
$arModuleClasses = require $currentDir.'/classes/.classes.php';
foreach ($arModuleClasses as $classPath) {
	$classPath = $currentDir.'/'.$classPath;
	if(is_file($classPath)) {
		require_once $classPath;
	}
}
?>
