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

/*/

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
[INCLUDE_IT_LATER]
obx.sms

[END]
//*/?>