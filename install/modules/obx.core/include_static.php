<?php
$currentDir = dirname(__FILE__);
$arModuleClasses = require $currentDir.'/classes/.classes.php';
foreach ($arModuleClasses as $classPath) {
	$classPath = $currentDir.'/'.$classPath;
	if(is_file($classPath)) {
		require_once $classPath;
	}
}
?>
