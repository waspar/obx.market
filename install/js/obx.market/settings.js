/******************************************
 ** @product Market-Start Bitrix Module **
 ** @vendor OBX Studio                  **
 ** @mailto info@a-68.ru                **
 ******************************************/
if(typeof(OBX) == 'undefined') {
	OBX = {};
}
if(typeof(OBX.Market) == 'undefined') {
	OBX.Market = {
		BXSettings: {}
	};
}
(function($) {
	$.fn.toggleDisabled = function(){
		return this.each(function(){
			this.disabled = !this.disabled;
		});
	};
})(jQuery);


(function(undefined){
	if(typeof(jQuery) == 'undefined') return;

	var settings_currency_url = '/bitrix/admin/ajax/obx_market_settings_currency.php';
	var $currencyContainer = '#obx_market_settings_currency_edit_table';
	var $settingsForm = '#obx_market_settings form';
	var tmplNewCurrencyRow = '#obx_market_currency_row_tmpl';

	var FieldDefaults = {
		 currency: ''
		,name: ''
		,sort: 100
		,format: '#.%'
		,thousSep: ' '
		,decPrecision: 2
	};

	$(function(){
		$currencyContainer = $($currencyContainer);
		$settingsForm = $($settingsForm);
		tmplNewCurrencyRow = $(tmplNewCurrencyRow).html();
		setEventHandlers();
	});

	var saveCurrencyTabData = function() {
		var rawSettingsFormData = $settingsForm.serializeArray();
		var jsonSettingsFormData = OBX.getSerializedFormAsObject(rawSettingsFormData);
		var jsonCurrencyData = {
			obx_currency_update: jsonSettingsFormData.obx_currency_update,
			obx_currency_delete: jsonSettingsFormData.obx_currency_delete,
			obx_currency_new: jsonSettingsFormData.obx_currency_new,
			obx_currency_default: jsonSettingsFormData.obx_currency_default
		};
		$.ajax({
			url: settings_currency_url
			,type : 'POST'
			,headers: { 'X-OBX-MarketSettings': true }
			,dataType : 'html'
			,data: jsonCurrencyData
			//,beforeSend: function(jqXHR, settings) {}
			,success: function(data, textStatus, jqXHR) {
				$currencyContainer.html(data);
				setEventHandlers();
				OBX.Market.BXSettings.showPriceList();
				OBX.Market.BXSettings.reloadNewPriceTemplate();
			}
			//,error: function(jqXHR, textStatus, errorThrown) {}
			//,complete: function(jqXHR, textStatus) {}
		});
	};

	var setEventHandlers = function() {
		var newCurrencyIndex = 0;
		$currencyContainer.find('input.add_new_item').on('click', function() {
			OBX.Market.BXSettings.addCurrencyRow($.extend(FieldDefaults, {
				 index: newCurrencyIndex
			}));
			newCurrencyIndex++;
		});

		$('#obx_currency_btn_save').on('click', saveCurrencyTabData);
		$('#obx_currency_btn_cancel').on('click', function() {
			OBX.Market.BXSettings.showCurrencyList();
		});
		setPerRowHandlers($('#obx_market_settings_currency'));
	};
	var setPerRowHandlers = function($parentArg) {
		var checkThusSepSpaceHandler = function() {
			var $this = $(this);
			var $parent = $this.parent().parent();
			var $thous_sep = $parent.find('.thous_sep');
			if($this.is(':checked')) {
				$thous_sep.attr('data-value', $thous_sep.attr('value'));
				$thous_sep.attr('value', ' ');
				$thous_sep.attr('readonly', 'readonly');
			}
			else {
				$thous_sep.attr('value', $thous_sep.attr('data-value'));
				$thous_sep.removeAttr('readonly');
			}
		}
		var $thousSep = $parentArg.find('input.thous_sep_space');
		$thousSep.on('change', checkThusSepSpaceHandler);
		$thousSep.each(checkThusSepSpaceHandler);

		var $removeNewBtn = $parentArg.find('.remove_new_item input');
		$removeNewBtn.each(function() {
			var $this = $(this);
			var newCurrencyRowIndex = $this.attr('data-new-row');
			var $newCurrencyRows = $currencyContainer.find('tr[data-new-row='+newCurrencyRowIndex+']');
			$this.on('click', function() {
				$newCurrencyRows.remove();
			});
		});
	};

	OBX.Market.BXSettings.addCurrencyRow = function(Fields) {
		var PlaceHolders = $.extend({}, FieldDefaults);
		if(typeof(Fields.thousSep) != 'undefined' && Fields.thousSep == ' ') {
			PlaceHolders.thousSpaceSepChecked = 'checked="checked"';
		}
		if( typeof(Fields.decPrecision) != 'undefined' ) {
			var strSelected = 'selected="selected"';
			switch(Fields.decPrecision) {
				case '1':
				case 1:
					PlaceHolders.decimalPrecisionSelected_1 = strSelected;
					break;
				case '2':
				case 2:
					PlaceHolders.decimalPrecisionSelected_2 = strSelected;
					break;
				case '3':
				case 3:
					PlaceHolders.decimalPrecisionSelected_3 = strSelected;
					break;
				case '4':
				case 4:
					PlaceHolders.decimalPrecisionSelected_4 = strSelected;
					break;
				case '5':
				case 5:
					PlaceHolders.decimalPrecisionSelected_5 = strSelected;
					break;
				default:
					PlaceHolders.decimalPrecisionSelected_5 = '';
					break;
			}
		}
		var $replaceRow = $currencyContainer.find('tr.replace');
		var sNewRow = tmplNewCurrencyRow;
		for(field in PlaceHolders) {
			sNewRow = sNewRow.replace(new RegExp('\\$'+field, 'g'), PlaceHolders[field]);
		}
		$replaceRow.replaceWith(sNewRow);
		setPerRowHandlers($currencyContainer.find('tr[data-new-row='+Fields.index+']'));
	};

	OBX.Market.BXSettings.showCurrencyList = function() {
		var $currencyContainer = $('#obx_market_settings_currency_edit_table');
		$.ajax({
			url: settings_currency_url
			,type : 'GET'
			,dataType : 'html'
			,success: function(data, textStatus, jqXHR) {
				$currencyContainer.html(data);
				setEventHandlers();
			}
		});
	};
})();

(function(undefined){
	if(typeof(jQuery) == 'undefined') return;

	var pricesScripts = '#obx_market_settings_price_scripts';
	var settings_price_url = '/bitrix/admin/ajax/obx_market_settings_price.php';

	$(function(){
		setEventHandlers();
	});

	var savePriceTabData = function() {
		var $priceContainer = $('#obx_market_settings_price_edit_table');
		var $settingsForm = $('#obx_market_settings form');
		var rawSettingsFormData = $settingsForm.serializeArray();
		var jsonSettingsFormData = OBX.getSerializedFormAsObject(rawSettingsFormData);

		var jsonPriceData = {
			obx_price: jsonSettingsFormData.obx_price,
			obx_price_update: jsonSettingsFormData.obx_price_update,
			obx_price_delete: jsonSettingsFormData.obx_price_delete,
			obx_price_new: jsonSettingsFormData.obx_price_new,
			obx_price_ugrp : jsonSettingsFormData.obx_price_ugrp
		};
		$.ajax({
			url: settings_price_url
			,type : 'POST'
			,headers: { 'X-OBX-MarketSettings': true }
			,dataType : 'html'
			,data: jsonPriceData
			//,beforeSend: function(jqXHR, settings) {}
			,success: function(data, textStatus, jqXHR) {
				$priceContainer.html(data);
				setEventHandlers();
				OBX.Market.BXSettings.showCatalogList();
			}
			//,error: function(jqXHR, textStatus, errorThrown) {}
			//,complete: function(jqXHR, textStatus) {}
		});
	};
	var setEventHandlers = function() {
		var newPriceIndex = Number($("#obx_market_settings_price_edit_table input[name^=obx_price_update]:last").val())+1;
		$('#obx_market_settings_price input.add_new_item').on('click', function() {
			var $container = $('#obx_market_settings_price');
			var $replaceRow = $container.find('tr.replace');
			var sNewRow = $('#obx_market_price_row_tmpl').html();
			//console.log(sNewRow);
			sNewRow = sNewRow.replace(/\$index/g, newPriceIndex);
			$replaceRow.replaceWith(sNewRow);
			setPerRowHandlers($container.find('tr[data-new-row='+newPriceIndex+']'));
			newPriceIndex++;
		});
		var setPerRowHandlers = function($parentArg) {
			var $removeNewBtn = $parentArg.find('.remove_new_item');
			$removeNewBtn.each(function() {
				var $this = $(this);
				var $parentRow = $this.parentsUntil('tr[data-new-row]').parent();
				$this.on('click', function() {
					$parentRow.remove();
				});
			});
			var $link = $("#obx_market_settings_price .add-new-group");
			$link.on("click", function () {
				$this = $(this);
				$parent = $this.closest("td").find(".group_container");
				$clone = $($parent.find(".group_select").last()).clone();
				$select = $clone.find("select");

				priceval = $select.attr("data-price-id");
				nextval = Number($select.attr("data-count-id")) + 1;

				selectname = "obx_price_ugrp["+priceval+"]["+nextval+"]";

				$select.attr("name",selectname);
				$select.attr("data-price-id",priceval);
				$select.attr("data-count-id",nextval);

				$selected = $clone.find("option[selected]");
				$selected.removeAttr("selected");

				$clone.appendTo($parent);
			});
		};
		$('#obx_price_btn_save').on('click', savePriceTabData);
		$('#obx_price_btn_cancel').on('click', OBX.Market.BXSettings.showPriceList);
		setPerRowHandlers($('#obx_market_settings_price'));
	};

	OBX.Market.BXSettings.showPriceList = function() {
		var $priceContainer = $('#obx_market_settings_price_edit_table');
		$.ajax({
			url: settings_price_url
			,type : 'GET'
			,dataType : 'html'
			,success: function(data, textStatus, jqXHR) {
				$priceContainer.html(data);
				setEventHandlers();
			}
		});
	};

	OBX.Market.BXSettings.reloadNewPriceTemplate = function() {
		var $pricesScripts = $(pricesScripts);
		$.ajax({
			url: settings_price_url
			,type : 'POST'
			,data: {obx_price_reload_new_price_tmpl: "Y"}
			,dataType : 'html'
			,success: function(data, textStatus, jqXHR) {
				$pricesScripts.html(data);
			}
		});
	};
})();

(function(undefined){
	if(typeof(jQuery) == 'undefined') return;
	var catalogSettings = '#obx_market_settings_catalog';
	var catalogScripts = '#obx_market_settings_catalog_scripts';
	var settings_catalog_url = '/bitrix/admin/ajax/obx_market_settings_catalog.php';
	var $catalogSettings;

	$(function(){
		$catalogSettings = $(catalogSettings);
		setEventHandlers();
	});

	OBX.Market.BXSettings.showCatalogList = function() {
		var $catalogContainer = $('#obx_market_settings_catalog_edit_table');
		$.ajax({
			url: settings_catalog_url
			,type : 'GET'
			,dataType : 'html'
			,success: function(data, textStatus, jqXHR) {
				$catalogContainer.html(data);
				setEventHandlers();
			}
		});
	};

	var saveCatalogTabData = function() {
		var $catalogContainer = $('#obx_market_settings_catalog_edit_table');
		var $settingsForm = $('#obx_market_settings form');
		var rawSettingsFormData = $settingsForm.serializeArray();
		var jsonSettingsFormData = OBX.getSerializedFormAsObject(rawSettingsFormData);

		var jsonCatalogData = {
			obx_ecom_iblock_save: jsonSettingsFormData.obx_ecom_iblock_save,
			obx_iblock_is_ecom: jsonSettingsFormData.obx_iblock_is_ecom,
			obx_ib_price_prop: jsonSettingsFormData.obx_ib_price_prop
		};
		$.ajax({
		 url: settings_catalog_url
		 ,type : 'POST'
		 ,headers: { 'X-OBX-MarketSettings': true }
		 ,dataType : 'html'
		 ,data: jsonCatalogData
		 ,success: function(data, textStatus, jqXHR) {
		 	$catalogContainer.html(data);
		 	setEventHandlers();
		 }
		 });
	};

	var setEventHandlers = function() {
		var $ecomCheckbox = $catalogSettings.find('input.obx_iblock_is_ecom');
		var $saveButton = $catalogSettings.find('input.obx_ecom_iblock_save');
		var $cancelButton = $catalogSettings.find('input.obx_ecom_iblock_cancel')
		$ecomCheckbox.on('click', function() {
			var $this = $(this);
			var bChecked = $this.get(0).checked;
			var checkedText = $this.attr('data-checked-text');
			var unCheckedText = $this.attr('data-unchecked-text');
			var iblockID = $this.attr('data-iblock-id');
			var $ibpPriceControll = $('.ibpprice-link-control[data-iblock-id='+iblockID+']');
			var $labelSpan = $this.parent().find('span.label-text');
			if(bChecked) {
				$labelSpan.html(checkedText);
			}
			else {
				$labelSpan.html(unCheckedText);
			}
			$ibpPriceControll.toggleClass('iblock-is-not-ecom');
			$ibpPriceControll.find("select").toggleDisabled();

		});
		$saveButton.on('click', saveCatalogTabData);
		$cancelButton.on('click', OBX.Market.BXSettings.showCatalogList);
	};

})();
(function (undefined) {
	$(function(){
		var lastActiveTab = $.cookie("obx_lastActiveTab");
		if (lastActiveTab !== null){
			tabSettings.SelectTab(lastActiveTab);
		}
		var $tabs = $(".tab-container");
		$tabs.on("click",function(){
			$this = $(this);
			tabId = $this.attr("id").replace("tab_cont_","");
			$.cookie("obx_lastActiveTab",tabId);
		});
	})
})()
