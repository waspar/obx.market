<?
/***********************************************
 ** @product OBX:Market Bitrix Module         **
 ** @authors                                  **
 **         Maksim S. Makarov aka pr0n1x      **
 ** @license Affero GPLv3                     **
 ** @mailto rootfavell@gmail.com              **
 ** @copyright 2013 DevTop                    **
 ***********************************************/

IncludeModuleLangFile(__FILE__);

class OBX_Market_BXMainEventsHandlers
{
	static public function OnbBuildGlobalMenu(&$aGlobalMenu) {
		IncludeModuleLangFile(__FILE__);
		$mainMenu = array(
			"obx_market_global_menu" => array(
				"icon" => 'button_obx_market',
				"page_icon" => 'obx_market_title_icon',
				"index_icon" => 'obx_market_page_icon',
				"text" => GetMessage("GLOB_MENU_OBX_MARKET_TEXT"),
				"title" => GetMessage("GLOB_MENU_OBX_MARKET_TITLE"),
				"url" => 'obx_market_index.php',
				"sort" => 500,
				"items_id" => 'obx_market_global_menu',
				"help_section" => 'obx_market',
				//"items" => self::getGlobalMenuItems()
			)
		);
		$aGlobalMenu=array_merge($aGlobalMenu, $mainMenu);
	}


	static public function getGlobalMenuItems() {
		return array(
			"1" => array(
				"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_1_TEXT"),
				"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_1_TITLE"),
				"url" => 'obx_market_orders.php',
				"more_url" => array(
					"0" => 'obx_market_order_edit.php'
				),
				"parent_menu" => 'obx_market_global_menu',
				"sort" => 110,
				"icon" => 'obx_market_menu_icon_orders',
				"page_icon" => 'obx_market_page_icon_orders'
			),

			"2" => array(
				"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_2_TEXT"),
				"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_2_TITLE"),
				"url" => 'obx_market_order_props.php',
				"more_url" => array(
					"0" => 'obx_market_order_props_edit.php'
				),
				"parent_menu" => 'obx_market_global_menu',
				"sort" => 120,
				"icon" => 'obx_market_menu_icon_order_props',
				"page_icon" => 'obx_market_page_icon_order_props'
			),

			"3" => array(
				"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_3_TEXT"),
				"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_3_TITLE"),
				"url" => 'obx_market_order_status.php',
				"more_url" => array(
					"0" => 'obx_market_order_status_edit.php'
				),
				"parent_menu" => 'obx_market_global_menu',
				"sort" => 130,
				"icon" => 'obx_market_menu_icon_order_status',
				"page_icon" => 'obx_market_page_icon_order_status'
			),

//					"4" => array(
//						"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_4_TEXT"),
//						"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_4_TITLE"),
//						"url" => 'obx_market_places.php',
//						"parent_menu" => 'obx_market_global_menu',
//						"sort" => 140,
//						"icon" => 'obx_market_menu_icon_places',
//						"page_icon" => 'obx_market_page_icon_places'
//					),
//
//					"5" => array(
//						"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_5_TEXT"),
//						"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_5_TITLE"),
//						"url" => 'obx_market_pay_systems.php',
//						"parent_menu" => 'obx_market_global_menu',
//						"sort" => 150,
//						"icon" => 'obx_market_menu_icon_pay_systems',
//						"page_icon" => 'obx_market_page_icon_pay_systems'
//					),
//
//					"6" => array(
//						"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_6_TEXT"),
//						"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_6_TITLE"),
//						"url" => 'obx_market_delivery_systems.php',
//						"parent_menu" => 'obx_market_global_menu',
//						"sort" => 160,
//						"icon" => 'obx_market_menu_icon_delivery_systems',
//						"page_icon" => 'obx_market_page_icon_delivery_systems'
//					),
//					"7" => array(
//						"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_7_TEXT"),
//						"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_7_TITLE"),
//						"url" => 'obx_market_statistics.php',
//						"parent_menu" => 'obx_market_global_menu',
//						"sort" => 170,
//						"icon" => 'obx_market_menu_icon_statistics',
//						"page_icon" => 'obx_market_page_icon_statistics'
//
//					),

			"8" => array(
				"text" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_8_TEXT"),
				"title" => GetMessage("GLOB_MENU_OBX_MARKET_ITEM_8_TITLE"),
				"url" => '/bitrix/admin/settings.php?lang='.LANG.'&mid=obx.market&mid_menu=1',
				"parent_menu" => 'obx_market_global_menu',
				"sort" => 180,
				"icon" => 'obx_market_menu_icon_market_options',
				"page_icon" => 'obx_market_page_icon_market_options'
			),
		);
	}
}
