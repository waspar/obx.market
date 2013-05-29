/***************************************
 ** @product OBX:Market Bitrix Module **
 ** @authors                          **
 **         Maksim S. Makarov         **
 **         Morozov P. Artem          **
 ** @License GPLv3                    **
 ** @mailto rootfavell@gmail.com      **
 ** @mailto tashiro@yandex.ru         **
 ***************************************/

/*
// Пример использоваения
//basket
//	.add2Basket({2:2}, false)
//	.add2Basket({5:5, 3:3}, false)
//	.add2Basket([{6:6}, {4:4}], false);
//	.add2Basket({20:1, 19:1, 18:1}, false);
// или даже так:
 .add2Basket({20:1, 19:1, 18:1}, false);


примерно так добавлять события
 basket.onBeforeItemAdd(function(event, item, bAnimate) {
 console.log('onBeforeItemAdd');
 console.log(event, item, bAnimate);
 });
 basket.onAfterItemAdd(function(event, item, bAnimate) {
 console.log('onAfterItemAdd');
 console.log(event, item, bAnimate);
 });

 basket.onBeforeItemRemove(function(event, item, bAnimate) {
 console.log('onBeforeItemRemove');
 console.log(event, item, bAnimate);
 });
 basket.onAfterItemRemove(function(event, item, bAnimate) {
 console.log('onAfterItemRemove');
 console.log(event, item, bAnimate);
 });

 basket.onBeforeItemUpdate(function(item, qty, delta, bAnimate) {
 console.log('onBeforeItemUpdate');
 console.log(item, qty, delta, bAnimate);
 });
 basket.onAfterItemUpdate(function(item, qty, delta, bAnimate) {
 console.log('onAfterItemUpdate');
 console.log(item, qty, delta, bAnimate);
 });

 basket.onBeforeItemRender(function() {
 console.log('onBeforeItemRender');
 });
 basket.onAfterItemRender(function() {
 console.log('onAfterItemRender');
 });
 */


if(typeof(jQuery) == 'undefined') jQuery = false;
(function($, undefined) {
	if(!$) return false;

	// default conf
	var defaults= {
		api:					true
		,template:				false
		,animateClose:			true
		,round:					0
		,qty:					1
		,durationClose:			300
		,qtyLimit:				999
		,plusClass:				'.plus'
		,minusClass:			'.minus'
		,closeClass:			'.close'
		,itemClass:				'.item'
		,totalClass:			'.label .basket-cost'
		,itemsContainer:		'.basket-content'
		,toBasketButtons:		true // true - on, false - off
		,toBasketClass:			'.addtobasket'
		,toBasketAddedClass:	'added'
		,toBasketContainer:		'#content'
		,qtyInput:				'input[name=qty]'
		,animate:{
			steps : 10, // from 3 to  15
			duration : 500 // from 200 to 1000
		}
		,ajaxSend: true
		,ajaxUrl: ''
		,scrollBasketWhenQty: 4
		,mouseWheelSpeed: 20
	};

	/**
	 * @deprecated
	 * @param price
	 * @returns {string}
	 */
	var getDisplayPrice = function(price){ // converts 15360 to 15 360 (not for float)
		if(price) return price.toString().replace(/(\d)(?=(\d{3})+$)/g, "$1 ");
		else return '';
	};


	// constructor
	function OBX_Basket(root, conf) {
		// current instance
		var self = this;
		self.$ = $(self);

		// private functions
		var jqBasketSetPrice = function(){ // set total basket cost in html node
			if(jq.total.length && basket.total){
				jq.total.text(getDisplayPrice(basket.total));
			}else return false;
		};
		var jqBasketAnimatePrice = function(from){ // animate total basket cost in html node
			from = parseFloat(from);
			to = parseInt(basket.total);
			if(from==to) return false; // no changes!

			if(jq.total.length && from>=0 && to>=0){
				// stop the previous animation
				if(jq.total.animatePriceInterval) clearInterval(jq.total.animatePriceInterval);
				// zeroing
				var duration=0, delta=0, direction=0, steps=0, stepDuration=0, stepDelta=0, fault=0, step=0, tmpPrice = 0;
				// setup
				if(conf.animate.steps && conf.animate.steps>3 && conf.animate.steps<15) steps = conf.animate.steps-0;
					else steps = 10;
				if(conf.animate.duration && conf.animate.duration>200 && conf.animate.duration<1000) duration = conf.animate.duration-0;
					else duration = 500;
				// calculation
				stepDuration = Math.floor(duration/steps);
					if(!stepDuration || stepDuration<10) return false;
				delta = Math.abs(parseInt(from-to));
					if(delta<=0) return false;
				direction = (to-from)<0 ? -1 : 1;
				stepDelta = Math.floor(Math.abs(delta/steps));
				fault = delta-(stepDelta*steps);
				// price animation
				jq.total.animatePriceInterval = setInterval(function(){

					if(direction>0){ // to up
						if(step==0) tmpPrice = from+stepDelta+fault-0;
						else tmpPrice = tmpPrice+stepDelta-0;
					}else{ // to down
						if(step==0) tmpPrice = from-stepDelta-fault-0;
						else tmpPrice = tmpPrice-stepDelta-0;
					}
					if(tmpPrice>999) jq.total.text(getDisplayPrice(tmpPrice)); // TODO: formatting price
					else jq.total.text(tmpPrice); // not need formatting price

					step++;

					// end?
					if(step>=steps || tmpPrice==to) clearInterval(jq.total.animatePriceInterval);
				}, stepDuration);

			}else return false;
		};

		// private vars
		var basket = {
				currency: {
					name: 'default'
					,format: {
						 string: '#'
						,dec_point: '.'
						,dec_precision: 2
						,thousands_sep: ' '
					}
				}, items:{}, total:0, count: 0
			},
			items = [],
			itemsIDIndex = {}, // ratio of ids with the keys of the items array
			jq = {},
			keyboardKeyControl = true,
			itemTemplateSetup = false,
			bActiveJScrollPane = false;

		/**
		 * number_format implementation from phpjs.org
		 * Licensed under MIT
		 */
		var number_format = function(number, decimals, dec_point, thousands_sep) {
			// http://kevin.vanzonneveld.net
			// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// +     bugfix by: Michael White (http://getsprink.com)
			// +     bugfix by: Benjamin Lupton
			// +     bugfix by: Allan Jensen (http://www.winternet.no)
			// +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
			// +     bugfix by: Howard Yeend
			// +    revised by: Luke Smith (http://lucassmith.name)
			// +     bugfix by: Diogo Resende
			// +     bugfix by: Rival
			// +      input by: Kheang Hok Chin (http://www.distantia.ca/)
			// +   improved by: davook
			// +   improved by: Brett Zamir (http://brett-zamir.me)
			// +      input by: Jay Klehr
			// +   improved by: Brett Zamir (http://brett-zamir.me)
			// +      input by: Amir Habibi (http://www.residence-mixte.com/)
			// +     bugfix by: Brett Zamir (http://brett-zamir.me)
			// +   improved by: Theriault
			// +      input by: Amirouche
			// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			// *     example 1: number_format(1234.56);
			// *     returns 1: '1,235'
			// *     example 2: number_format(1234.56, 2, ',', ' ');
			// *     returns 2: '1 234,56'
			// *     example 3: number_format(1234.5678, 2, '.', '');
			// *     returns 3: '1234.57'
			// *     example 4: number_format(67, 2, ',', '.');
			// *     returns 4: '67,00'
			// *     example 5: number_format(1000);
			// *     returns 5: '1,000'
			// *     example 6: number_format(67.311, 2);
			// *     returns 6: '67.31'
			// *     example 7: number_format(1000.55, 1);
			// *     returns 7: '1,000.6'
			// *     example 8: number_format(67000, 5, ',', '.');
			// *     returns 8: '67.000,00000'
			// *     example 9: number_format(0.9, 0);
			// *     returns 9: '1'
			// *    example 10: number_format('1.20', 2);
			// *    returns 10: '1.20'
			// *    example 11: number_format('1.20', 4);
			// *    returns 11: '1.2000'
			// *    example 12: number_format('1.2000', 3);
			// *    returns 12: '1.200'
			// *    example 13: number_format('1 000,50', 2, '.', ' ');
			// *    returns 13: '100 050.00'
			// Strip all characters but numerical ones.
			number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
			var n = !isFinite(+number) ? 0 : +number,
				prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
				sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
				dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
				s = '',
				toFixedFix = function (n, prec) {
					var k = Math.pow(10, prec);
					return '' + Math.round(n * k) / k;
				};
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
			if (s[0].length > 3) {
				s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
			}
			if ((s[1] || '').length < prec) {
				s[1] = s[1] || '';
				s[1] += new Array(prec - s[1].length + 1).join('0');
			}
			return s.join(dec);
		};

		// api
		$.extend(self, {
			setCurrency: function(currency) {
				basket.currency = $.extend({}, currency);
				if( typeof(basket.currency.format) == 'undefined' ) {
					basket.currency.format = {
						string: '#',
						dec_point: '.',
						dec_precision: 2,
						thousands_sep: ' '
					};
				}
				else {
					if( typeof(basket.currency.format.string) == 'undefined' ) {
						basket.currency.format.string = '#';
					}
					if( typeof(basket.currency.format.dec_point) == 'undefined' ) {
						basket.currency.format.dec_point = '.';
					}
					if( typeof(basket.currency.format.dec_precision) == 'undefined' ) {
						basket.currency.format.dec_precision = 2;
					}
					if( typeof(basket.currency.format.thousands_sep) == 'undefined' ) {
						basket.currency.format.thousands_sep = ' ';
					}
				}
				basket.currency.format.dec_precision = basket.currency.format.dec_precision|0;
			}
			,formatPrice: function(priceValue, formatArg) {
				var format = null;
				if( typeof(formatArg) == 'undefined') {
					format = $.extend({}, basket.currency.format);
				}
				else {
					format = $.extend({}, formatArg);
				}
				return format.string.replace(
						/#/,
						number_format(
							priceValue,
							format.dec_precision,
							format.dec_point,
							format.thousands_sep));
			}
			,setAjaxSending: function(bSend) {
				conf.ajaxSend = bSend?true:false;
			}
			,addPageItem : function(oItems){ // add from 1 object
				var id = 0, i=0;
				if(!$.obx.tools.isObject(oItems) || !oItems.id) return false;
				id = parseInt(oItems.id, 10);
				i = items.length;
				items.push(oItems);
				itemsIDIndex[id] = i;
				return self;
			}
			,removePageItem : function(id){
				if(!id) return false;
				if(itemsIDIndex.hasOwnProperty(id)){
					var key = itemsIDIndex[id];

					if(basket.items[id]) self.removeBasketItem(id); // remove from basket

					delete(items[key]);
					delete(itemsIDIndex[id]);
					return self;
				}
				return false;
			}
			,addPageItems : function(aoItems){ // array or object
				var id = 0, i=0;
				switch (true){
					case ($.obx.tools.isArray(aoItems)): // add from array
						for(var k in aoItems){
							id = parseInt(aoItems[k].id, 10);
							if(!id || itemsIDIndex.hasOwnProperty(id)) continue;
							i = items.length;
							items.push(aoItems[k]);
							itemsIDIndex[id] = i;
						}
					break;
					case ($.obx.tools.isObject(aoItems)):
						if(aoItems.id){ // only 1 item object
							self.addPageItem(aoItems);
							break;
						}
						for(var p in aoItems){ // multiple item object
							if(!aoItems[p].id) continue;
							if(itemsIDIndex.hasOwnProperty(p)) continue;
							i = items.length;
							items.push(aoItems[p]);
							itemsIDIndex[p] = i;
						}
					break;
					default:
						return false; //error
				}
				return self;
			}
			,setPageItems : function(aoItems){
				if($.obx.tools.isArray(aoItems) || $.obx.tools.isObject(aoItems)){
					items = []; // zeroing
					itemsIDIndex = {};
					self.addPageItems(aoItems);
					return self;
				}
				return false;
			}
			,getPageItems : function(){
				return items;
			}
			,getPageItem : function(id){
				if(!id) return false;
				if(itemsIDIndex.hasOwnProperty(id)) return items[itemsIDIndex[id]];
				else return false;
			}
			,countPageItems : function(){
				return items.length;
			}
			,add2Basket : function(item, bAnimate){ // item - id or array or object , bAnimate - animate basket total cost?
				if(!item || !jq.template || !jq.container || itemTemplateSetup!==true) return false; // error!
				if(bAnimate!==false) bAnimate=true; // animate basket total cost?
				var filling = {};
				switch (true){
					case ($.obx.tools.isInteger(item)):
						filling[item]=1; // qty = 1
					break;
					case ($.obx.tools.isArray(item)):
						for (var k in item)
							for(var p in item[k]) filling[p] = item[k][p] ? item[k][p] : 1;
					break;
					case ($.obx.tools.isObject(item)):
						if(item.id) filling[item.id] = item.q ? item.q : 1;
						else for(var p in item) filling[p] = item[p] ? item[p] : 1;
					break;
					default:
						return false;
				}
				self.$.trigger('onBeforeItemAdd', [item, bAnimate]);
				// exe
				var item, key, qty, price, from;
				for(var id  in filling){ // item id
					if(basket.items[id]) continue; // already added
					key = itemsIDIndex[id]-0; // key
						if(!(key>=0)) continue;
					item = items[key]; // item object
						if(!item) continue;
					qty = parseInt(filling[id], 10); // item quality
						if(!qty) qty=1;
					price = parseFloat(item.price).toFixed(conf.round); // item price
						if(!price) continue;
					// basket
					basket.items[id] = {qty:qty, price:price, cost:price*qty}; // basket item set
						from = basket.total;
					basket.total += price*qty; // basket total cost
					basket.count++;
					// buttons
					if(conf.toBasketButtons)
						jq.buttons.filter('[data-id='+id+']').addClass(conf.toBasketAddedClass);
					// item render
					//var e = $.Event('onBeforeItemRender');
					self.$.trigger('onBeforeItemRender', [item, bAnimate]);
					jq.template.tmpl(items[key], jtmplTools).appendTo(jq.container);
					self.$.trigger('onAfterItemRender', [item, bAnimate]);
					self.$.trigger('onAfterItemAdd', [item, bAnimate]);
					// basket total cost update
					if(bAnimate){
						jqBasketAnimatePrice(from);
					}else jqBasketSetPrice();
				}
				//ajax
				ajaxQuery({add:filling});
				return self;
			}
			,updateBasketItem : function(item, qty, delta, bAnimate){ // bAnimate - animate? default = false
				if(itemTemplateSetup!==true) return false; // error!
				if(bAnimate!==true) bAnimate = false;
				qty = parseInt(qty);
				delta = parseInt(delta);
				if(!(qty>=0) && !delta) return false; // error
				var $item=null, tmplItem=null, id=0;
				self.$.trigger('onBeforeItemUpdate', [item, qty, delta, bAnimate]);
				switch (true){
					// update from id
					case ($.obx.tools.isInteger(item)):
						id = item-0; // id
						$item = jq.container.find(conf.itemClass+'[data-id='+id+']'); // jq item
						tmplItem = $item.tmplItem(); // jq tmpl
						// check
						if(!$item.length || !tmplItem.key) return false;
						if(!basket.items[id]) return false;
					break;
					// update from jq set
					case ($.obx.tools.isJQset(item)):
						$item = item; // jq item
						tmplItem = $item.tmplItem(); // jq tmpl
						id = $item.attr('data-id')-0; // id
						// check
						if(!id || !tmplItem.key) return false;
						if(!basket.items[id]) return false;
					break;
					// update from jq tmpl
					case ($.obx.tools.isJQtmpl(item)):
						tmplItem = item; // jq tmpl
						$item = $(tmplItem.nodes[0]); // jq item
						if(!$item.length) return false; // check
						id = $item.attr('data-id'); //id
						if(!id) return false; // check
					break;
					// error
					default:
						return false;
					break;
				}
				// calculation
				var from = basket.total;
				if((qty>=0)){ // update
					if(qty==0){ // remove
						self.removeBasketItem(id, bAnimate);
						return self;
					}
					basket.total = basket.total-basket.items[id].cost;
					basket.items[id].qty  = qty;
					basket.items[id].cost  = qty*items[id].price;
					basket.total += basket.items[id].cost;
				}else if(delta){ // change
					if(delta<0 && basket.items[id].qty<=Math.abs(delta)){ // remove
						self.removeBasketItem(id, bAnimate);
						return self;
					}
					basket.total = basket.total-basket.items[id].cost;
					basket.items[id].qty  = basket.items[id].qty+delta;
					basket.items[id].cost  = qty*items[id].price;
					basket.total += basket.items[id].cost;
				}
				// item re-render
				tmplItem.update();
				// basket cost update
				if(bAnimate) jqBasketAnimatePrice(from);
				else jqBasketSetPrice();
				//ajax
				ajaxQuery({update:{id:id, qty:basket.items[id].qty}});
				return self;
			}
			,removeBasketItem : function(id, bAnimate){
				if(itemTemplateSetup!==true) return false; // error!
				id = id|0;
				if(bAnimate!==false) bAnimate = true;
				if(basket.items[id]){
					self.$.on('onBeforeItemRemove', [id, bAnimate]);
					// item
					$item = self.getBasketItem(id);
					if(!$item.length) return false;
					// calculation
					var from = basket.total;
					basket.total = basket.total-basket.items[id].cost; // remove from total cost
					basket.count--;
					delete(basket.items[id]); // remove from basket
					if(bAnimate) jqBasketAnimatePrice(from); // animate basket total cost
					else jqBasketSetPrice();
					// buttons
					if(conf.toBasketButtons) jq.buttons.filter('[data-id='+id+']').removeClass(conf.toBasketAddedClass);
					// animate & remove
					if(conf.animateClose && bAnimate){ // animate item?
						duration = conf.durationClose ? parseInt(conf.durationClose) : 300; // animate duration
						$item.animate({height: 0}, {duration: duration}); // animate
						setTimeout(function(){ // remove item
							$item.remove();
							self.$.trigger('onAfterItemRemove', [id, bAnimate]);
						}, duration);
					} else {
						$item.remove(); // remove item
						self.$.trigger('onAfterItemRemove', [id, bAnimate]);
					}
				}else return false;
				// ajax
				ajaxQuery({remove:id});
				return self;
			}
			,clearBasket: function() {
				for(var id in basket.items) {
					self.removeBasketItem(id, false);
				}
				clearInterval(jq.total.animatePriceInterval);
				jq.total.text('0');
				jq.container.text('');
			}
			,getBasketItem : function(id){
				id = id|0;
				if(basket.items[id]){
					return jq.container.find(conf.itemClass+'[data-id='+id+']');
				}
				return false;
			}
			,getBasketTotal: function() {
				return basket.total;
			}
			,getBasketCount: function() {
				return basket.count;
			}
			,setBasketItemsFromServer: function() {
				if (conf.ajaxSend) {
					ajaxQuery({}, {onAfterAjaxSuccess: function(data, textStatus, jqXHR) {
						self.addPageItems(data.products_list);
						self.clearBasket();
						self.add2Basket(data.items_list, false);
					}});
				} else {
					basket.count = root.find(".item").length;
				}
			}
			,setItemTemplate : function(id){
				jq.template = $(id);
				if(jq.template.length){
					itemTemplateSetup = true;
					return self;
				};
				itemTemplateSetup = false;
				return false;
			}
			,activateJScrollPane: function(startFromItemsQty, lessDevelTimeout) {
				if(!lessDevelTimeout) lessDevelTimeout = 1000;
				if(!startFromItemsQty) startFromItemsQty = conf.scrollBasketWhenQty;
				if( !$.isFunction($.fn.jScrollPane) ) {
					return false;
				}
				var jScrollPaneAPI = null;
				var basketScrollPane = function() {

					var bDestroyed = false;
					var $basketScrollable = root.find('.basket-scrollable');
					var enablingByCountCheck = function() {
						if(basket.count >= startFromItemsQty) {
							if(!jScrollPaneAPI) {
								$basketScrollable.data('jsp', undefined);
								jScrollPaneAPI = $basketScrollable.jScrollPane({
									mouseWheelSpeed : conf.mouseWheelSpeed,
								}).data().jsp;
							}
							//jScrollPaneAPI = $basketScrollable.jScrollPane().data('jsp');
						}
						else if(basket.count < startFromItemsQty && jScrollPaneAPI) {
							jScrollPaneAPI.destroy();
							jScrollPaneAPI = null;
							$basketScrollable = root.find('.basket-scrollable');
						}
					};
					enablingByCountCheck();
					self.onAfterItemAdd(function() {
						enablingByCountCheck();
						if(jScrollPaneAPI) {
							jScrollPaneAPI.reinitialise();
							jScrollPaneAPI.scrollToBottom();
						}
					});
					self.onAfterItemRemove(function() {
						enablingByCountCheck();
						if(jScrollPaneAPI) {
							jScrollPaneAPI.reinitialise();
						}
					});
				};
				// Таймаут нужен потому что jScrollPane может некорректно посчитать высоту блока
				// т.к. исполняется после отработки LESS.JS потому ожидаем секунду.
				// за секунду LESS.JS как правило успевает скомпилировать даже очень много стилей
				var bLessDevel = false;
				if (typeof(less)!="undefined") {
					if(less.env && less.env == 'development') {
						bLessDevel = true;
					}
				}
				if(bLessDevel) {
					setTimeout(basketScrollPane, lessDevelTimeout);
				}
				else {
					basketScrollPane();
				}
				bActiveJScrollPane = true;
				return jScrollPaneAPI;
			}
			
			,setAjaxURL: function() {
				
			}
		});





		// ajax
		var ajaxQueryID = 0;
		var ajaxTimeoutID = 0;
		var ajaxQuery = function(qdata, ajaxQueryConf){ // qdata is a query post params!
			if(conf.ajaxSend!==true) return true;
			if(conf.ajaxUrl) {
				if(ajaxTimeoutID) clearTimeout(ajaxTimeoutID); // clear previous ajax waiting
				ajaxTimeoutID = setTimeout(function(){ // take a pause
					if( typeof(ajaxQueryConf) == 'undefined' ) {
						ajaxQueryConf = {};
					}
					// exe
					if(ajaxQueryID) $.abort(ajaxQueryID); // abort previous ajax request
					if(!qdata || !$.obx.tools.isObject(qdata)) qdata = {};
					//qdata.browser_basket = basket.items; // send a basket set

					ajaxQueryID = $.ajax({
							// configuration
							url: conf.ajaxUrl
							,context : root
							,method : 'POST'
							,headers: { 'X-OBX_Basket': true }
							,dataType : 'json'
							,data : qdata
							,timeout : 3000
							// handlers
							,beforeSend: function(jqXHR, settings){
								if( typeof(ajaxQueryConf['onAjaxSend']) == 'function' ) {
									ajaxQueryConf['onAjaxSend'](jqXHR, settings);
								}
								self.$.trigger('onAjaxSend', [jqXHR, settings]);
							}
							,complete: function(jqXHR, textStatus) {
								ajaxQueryID = 0;
								if( typeof(ajaxQueryConf['onAjaxComplete']) == 'function' ) {
									ajaxQueryConf['onAjaxComplete'](jqXHR, textStatus);
								}
								self.$.trigger('onAjaxComplete', [jqXHR, textStatus]);
							}
							,error : function(jqXHR, textStatus, errorThrown){
								if( typeof(ajaxQueryConf['onAjaxError']) == 'function' ) {
									ajaxQueryConf['onAjaxError'](jqXHR, textStatus, errorThrown);
								}
								self.$.trigger('onAjaxError', [jqXHR, textStatus, errorThrown]);
							}
							,success : function(data, textStatus, jqXHR){
								if( typeof(ajaxQueryConf['onBeforeAjaxSuccess']) == 'function' ) {
									ajaxQueryConf['onBeforeAjaxSuccess'](data, textStatus, jqXHR);
								}
								self.$.trigger('onBeforeAjaxSuccess', [data, textStatus, jqXHR]);

								if( data.messages.length>0 ) {
									for(keyMessage in data.messages) {
										if( data.messages[keyMessage] && data.messages[keyMessage].TYPE == 'E') {
											alert(data.messages[keyMessage].TEXT);
											self.clearBasket();
											self.add2Basket(data.items_list, false);
										}
									}
								}
								ajaxQueryID = 0; // zeroing ajax id

								if( typeof(ajaxQueryConf['onAfterAjaxSuccess']) == 'function' ) {
									ajaxQueryConf['onAfterAjaxSuccess'](data, textStatus, jqXHR);
								}
								self.$.trigger('onAfterAjaxSuccess', [data, textStatus, jqXHR]);
							}
						}
					);


				}, 300); // ajax timeout
			}else return false;
		};





		// callbacks
		$.each([
			 'onBeforeItemAdd'
			,'onAfterItemAdd'
			,'onBeforeItemRemove'
			,'onAfterItemRemove'
			,'onBeforeItemUpdate'
			,'onAfterItemUpdate'
			,'onBeforeItemRender'
			,'onAfterItemRender'
			,'onAjaxSend'
			,'onAjaxComplete'
			,'onAjaxError'
			,'onBeforeAjaxSuccess'
			,'onAfterAjaxSuccess'

		], function(i, name){
			// configuration
			if ($.isFunction(conf[name])) {
				self.$.on(name, conf[name]);
			}
			self[name] = function(fn) {
				if (fn) { self.$.on(name, fn); }
				return self;
			};
		});

		// template tools
		jtmplTools = {
			getDisplayCost : function(){
				id = this.data.id;
				if(basket.items[id]){
					cost = basket.items[id].cost;
					//if(cost) return getDisplayPrice(cost);
					if(cost) return self.formatPrice(cost);
					else return '';
				}else return '';
			},
			getQty : function(){
				id = this.data.id;
				if(basket.items[id]){
					qty = basket.items[id].qty;
					if(qty) return qty; else return 1;
				}else return '';
			}
			,check : function(){
				if(!this.data.id ||
				   !this.data.price ||
				   !this.data.name) return false;
				this.data.price = parseFloat(this.data.price).toFixed(conf.round); // preparation price
				return true;
			},
			has : function (property){
				if(this.data.hasOwnProperty(property)) return true;
				else return false;
			}
		};


		// events handlers
		ehandlers = {
			close : function(e){
				e.preventDefault(); // if a - prevented click
				e.stopPropagation(); // only this event

				var $this = $(this);
				var $item = $this.parents(conf.itemClass);
				if($item.length){
					id = $item.attr('data-id');
					if(id) return self.removeBasketItem(id);
					else return false;
				}else return false;
			}
			,plus : function(e){
				e.preventDefault(); // if a - prevented click
				var $this = $(this);
				var $item = $this.parents(conf.itemClass);
				var tmplItem = $item.tmplItem();

				if($item.length && tmplItem.key){
					var id = $item.attr('data-id');
					if(basket.items[id]){

						if(basket.items[id].qty == conf.qtyLimit) return false; // limit
						// +++ что-то не понятное
						// item
						//if(items[id]>=0) return false;
						// ^^^ что-то не понятное
						// basket
						basket.items[id].qty++;
						price = parseFloat(parseFloat(basket.items[id].price).toFixed(conf.round));
						basket.items[id].cost = basket.items[id].qty*price;
						from = basket.total;
						basket.total = parseFloat(parseFloat(basket.total + price).toFixed(conf.round));
						// item re-render
						tmplItem.update();
						// basket cost update
						jqBasketAnimatePrice(from);
						// ajax
						ajaxQuery({update:{id:id, qty:basket.items[id].qty}});
					}else return false;
				}else return false;
			}
			,minus : function(e){
				e.preventDefault(); // if a - prevented click

				$this = $(this);
				$item = $this.parents(conf.itemClass);
				var tmplItem = $item.tmplItem();

				if($item.length && tmplItem.key){
					var id = $item.attr('data-id');
					if(basket.items[id]){
						// +++ что-то не понятное
						// item
						//if(items[id]>=0) return false;
						// ^^^ что-то не понятное

						// remove?
						if(basket.items[id].qty==1){
							if(confirm('Удалить товар "'+tmplItem.data.name+'" из корзины?')){
								self.removeBasketItem(id);
								return true;
							}else return false;
						}
						// basket
						basket.items[id].qty--;
						price = parseFloat(basket.items[id].price).toFixed(conf.round);
						basket.items[id].cost = basket.items[id].qty*price;
						from = basket.total;
						basket.total = basket.total - price;
						// item re-render
						tmplItem.update();
						// basket cost update
						jqBasketAnimatePrice(from);
						// ajax
						ajaxQuery({update:{id:id, qty:basket.items[id].qty}});

					}else return false;
				}else return false;
			}
			,keydown : function(e){
				// guide buttons (arrows)
				if(e.keyCode>=37 && e.keyCode<=40){
					keyboardKeyControl = false; // no changes
					return true;
				}
				// control buttons
				if(e.ctrlKey==true || e.altKey==true || e.shiftKey==true){
					return false;
				}else if(
					(e.keyCode>=48 && e.keyCode<=57) || // number line
						(e.keyCode>=96 && e.keyCode<=105) || // numbers of block Num
						(e.keyCode==8) || // backspace
						(e.keyCode==46) // delete
					){
					keyboardKeyControl = true; // anything changed
					return true;
				}else return false;
			}
			,keyup : function(){
				if(!keyboardKeyControl){ // keyboard control
					keyboardKeyControl = true;
					return false;
				}
				if(this.obxTimeoutKeyUp) clearTimeout(this.obxTimeoutKeyUp); // cancel
				// setup
				var $this = $(this);
				$item = $this.parents(conf.itemClass);
				var value = $this.val(); // typeof str!
				var tmplItem = $item.tmplItem();
				var id = $item.attr('data-id');
				// check
				if(value>999) return false;
				if(!id || !$.obx.tools.isJQtmpl(tmplItem)) return false;
				// exe
				if(!$.obx.tools.isEmpty(value)){
					// calculation
					if((value-0)===0){ // zero
						// remove?
						if(confirm('Удалить товар "'+tmplItem.data.name+'" из корзины?')){
							self.removeBasketItem(id);
							return true;
						}else{ // rollback
							$this.val(basket.items[id].qty);
							return true;
						}
					}
					this.obxTimeoutKeyUp = setTimeout(function(){ // timeout for exe
						// item
						basket.items[id].qty = value;
						var oldCost = basket.items[id].cost;
						// basket
						basket.items[id].cost = basket.items[id].qty*basket.items[id].price;
						var from = basket.total;
						basket.total = basket.total-oldCost+basket.items[id].cost;
						// item re-render
						tmplItem.update();
						// basket cost update
						jqBasketAnimatePrice(from);
					}, 500);
					return true;
				}else return true; // onchange make a rollback
			}
			,change : function(e){
				var $this = $(this);
				var value = $this.val();
				if($.obx.tools.isEmpty(value)){
					var $item = $(this).parents(conf.itemClass);
						if(!$item.length) return false;
					var id = $item.attr('data-id')-0;
						if(!id) return false;
					var tmplItem = $item.tmplItem();
						if(tmplItem.key) $this.val(basket.items[id].qty); // rollback
				}
			}
		};

		// jq tmpl item implementation
		if(conf.template) self.setItemTemplate(conf.template);

		// jq sets
		jq.container = root.find(conf.itemsContainer);
			if(!jq.container.length) return false;
		jq.total = root.find(conf.totalClass);
		jq.buttons = {};
		if(conf.toBasketClass){
			jq.buttons = $(conf.toBasketClass, conf.toBasketContainer ? conf.toBasketContainer : 'body');
		}

		// events implementation
		jq.container.on('click',    conf.closeClass,    ehandlers.close);
		jq.container.on('click',    conf.plusClass,     ehandlers.plus);
		jq.container.on('click',    conf.minusClass,    ehandlers.minus);
		jq.container.on('keydown',  conf.qtyInput,      ehandlers.keydown);
		jq.container.on('keyup',    conf.qtyInput,      ehandlers.keyup);
		jq.container.on('change',   conf.qtyInput,      ehandlers.change);


		// complete object
		return self;
	};


	// jQuery prototype implementation
	$.fn.OBX_Basket = function(conf){
		// jq namespace
		if(!$.hasOwnProperty('obx')){
			console.log('Needs main script obx!');
			return false;
		}
		// jq version
		if(!$.obx.tools.jqIsGeatThan(1, 7)){
			console.log('JQuery version is not enough (need > 1.7)!');
			return false;
		}
		// jq set
		if(!this.length) return false;
		// if already constructed --> return API
		var el = this.data("obxbasket");
		if (el) { return el; }
		conf = $.extend(true, {}, defaults, conf);
		if (!checkDepJScrollPane()) {
			console.log("jScrollPane not found!");
		}
		includeDepMousewheel();
		this.each(function() {
			var $this = $(this);
			el = new OBX_Basket($this, conf);
			el.activateJScrollPane();
			el.setBasketItemsFromServer();
			$this.data("obxbasket", el);
		});
		return conf.api ? el: this;
	};

	var includeDepMousewheel = function() {
		if( $.isFunction($.mousewheel) ) {
			return false;
		}
		(function($) {

			var types = ['DOMMouseScroll', 'mousewheel'];

			if ($.event.fixHooks) {
				for ( var i=types.length; i; ) {
					$.event.fixHooks[ types[--i] ] = $.event.mouseHooks;
				}
			}

			$.event.special.mousewheel = {
				setup: function() {
					if ( this.addEventListener ) {
						for ( var i=types.length; i; ) {
							this.addEventListener( types[--i], handler, false );
						}
					} else {
						this.onmousewheel = handler;
					}
				},

				teardown: function() {
					if ( this.removeEventListener ) {
						for ( var i=types.length; i; ) {
							this.removeEventListener( types[--i], handler, false );
						}
					} else {
						this.onmousewheel = null;
					}
				}
			};

			$.fn.extend({
				mousewheel: function(fn) {
					return fn ? this.bind("mousewheel", fn) : this.trigger("mousewheel");
				},

				unmousewheel: function(fn) {
					return this.unbind("mousewheel", fn);
				}
			});


			function handler(event) {
				var orgEvent = event || window.event, args = [].slice.call( arguments, 1 ), delta = 0, returnValue = true, deltaX = 0, deltaY = 0;
				event = $.event.fix(orgEvent);
				event.type = "mousewheel";

				// Old school scrollwheel delta
				if ( orgEvent.wheelDelta ) { delta = orgEvent.wheelDelta/120; }
				if ( orgEvent.detail     ) { delta = -orgEvent.detail/3; }

				// New school multidimensional scroll (touchpads) deltas
				deltaY = delta;

				// Gecko
				if ( orgEvent.axis !== undefined && orgEvent.axis === orgEvent.HORIZONTAL_AXIS ) {
					deltaY = 0;
					deltaX = -1*delta;
				}

				// Webkit
				if ( orgEvent.wheelDeltaY !== undefined ) { deltaY = orgEvent.wheelDeltaY/120; }
				if ( orgEvent.wheelDeltaX !== undefined ) { deltaX = -1*orgEvent.wheelDeltaX/120; }

				// Add event and delta to the front of the arguments
				args.unshift(event, delta, deltaX, deltaY);

				return ($.event.dispatch || $.event.handle).apply(this, args);
			}

		})(jQuery);
	};


	var checkDepJScrollPane = function() {
		if( $.isFunction($.fn.jScrollPane) ) {
			return true;
		} else {
			return false;
		}

	};
})(jQuery);
