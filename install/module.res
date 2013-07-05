#########################################
## @product OBX:Core Bitrix Module     ##
## @authors                            ##
##        Maksim S. Makarov aka pr0n1x ##
## @license Affero GPLv3               ##
## @mailto rootfavell@gmail.com        ##
## @copyright 2013 DevTop              ##
#########################################

[RESOURCES]
	%INSTALL_FOLDER%/admin/ :: obx_market_*.php :: %BX_ROOT%/admin/
	%INSTALL_FOLDER%/admin/ajax/ :: obx_market_*.php :: %BX_ROOT%/admin/ajax/
	%INSTALL_FOLDER%/themes/.default/ :: obx.market :: %BX_ROOT%/themes/.default/
	%INSTALL_FOLDER%/themes/.default/ :: obx.market.css :: %BX_ROOT%/themes/.default/
	%INSTALL_FOLDER%/components/ :: obx.market :: %BX_ROOT%/components/
	%INSTALL_FOLDER%/php_interface/event.d/ :: obx.market*.php :: %BX_ROOT%/php_interface/event.d/
	%INSTALL_FOLDER%/js/ :: obx.market :: %BX_ROOT%/js/
	%INSTALL_FOLDER%/tools/ :: obx.market :: %BX_ROOT%/tools/

[DEPENDENCIES]
	obx.core
	obx.sms

[END]
