<?php
if( !defined('OBX_Market_autoloadCompatClasses_DEF') ) {
	function OBX_Market_autoloadCompatClasses($className) {
		if(substr($className, 0, 4) == 'OBX_') {
			$moduleDir = realpath(dirname(__FILE__).'/../../');
			$className = str_replace('OBX_', 'OBX\\', $className);
			$arModuleClasses = require $moduleDir.'/classes/.classes.php';
			if( array_key_exists($className, $arModuleClasses) ) {
				$classFilePath = $moduleDir.'/'.$arModuleClasses[$className];
				require_once $classFilePath;
				require_once str_replace('/classes/', '/classes/.compat/', $classFilePath);
			}
		}
	}
	define('OBX_Market_autoloadCompatClasses_DEF', true);
	spl_autoload_register(OBX_Market_autoloadCompatClasses);
}
