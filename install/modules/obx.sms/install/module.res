[RESOURCES]
%INSTALL_FOLDER%/php_interface/obx.sms :: *.php :: %BX_ROOT%/php_interface/obx.sms/

[DEPENDENCIES]
obx.core

[RAW_LANG_CHECK]
{
	[classes]
		path: %MODULE_FOLDER%/classes
	[options]
		path: %MODULE_FOLDER%/options.php
	[install]
		path: %INSTALL_FOLDER%/
		exclude_path: %INSTALL_FOLDER%/modules/*
	[module.obx.core]
		path: %INSTALL_FOLDER%/modules/obx.core
		exclude_path: %INSTALL_FOLDER%/modules/obx.core/classes/Build.php
		exclude_path: %INSTALL_FOLDER%/modules/obx.core/test/*.php
		exclude_path: %INSTALL_FOLDER%/modules/obx.core/test/*/*.php
}
