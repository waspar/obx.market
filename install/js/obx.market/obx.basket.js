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
	};

	// public functions
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
		var basket ={items:{}, total:0, count: 0},
			items = [],
			keys = {}, // ratio of ids with the keys of the items array
			jq = {},
			keyboardKeyControl = true,
			itemTemplateSetup = false,
			bActiveJScrollPane = false;


		// api
		$.extend(self, {
			addPageItem : function(oItems){ // add from 1 object
				var id = 0, i=0;
				if(!$.obx.tools.isObject(oItems) || !oItems.id) return false;
				id = parseInt(oItems.id, 10);
				i = items.length;
				items.push(oItems);
				keys[id] = i;
				return self;
			}
			,removePageItem : function(id){
				if(!id) return false;
				if(keys.hasOwnProperty(id)){
					var key = keys[id];

					if(basket.items[id]) self.removeBasketItem(id); // remove from basket

					delete(items[key]);
					delete(keys[id]);
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
							if(!id || keys.hasOwnProperty(id)) continue;
							i = items.length;
							items.push(aoItems[k]);
							keys[id] = i;
						}
					break;
					case ($.obx.tools.isObject(aoItems)):
						if(aoItems.id){ // only 1 item object
							self.addPageItem(aoItems);
							break;
						}
						for(var p in aoItems){ // multiple item object
							if(!aoItems[p].id) continue;
							if(keys.hasOwnProperty(p)) continue;
							i = items.length;
							items.push(aoItems[p]);
							keys[p] = i;
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
					keys = {};
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
				if(keys.hasOwnProperty(id)) return items[keys[id]];
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
					key = keys[id]-0; // key
						if(!(key>=0)) continue;
					item = items[key]; // item object
						if(!item) continue;
					qty = parseInt(filling[id], 10); // item quality
						if(!qty) qty=1;
					price = parseFloat(item.price.toFixed(conf.round)); // item price
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
				id = parseInt(id);
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
				id = parseInt(id);
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
				ajaxQuery({}, {onAfterAjaxSuccess: function(data, textStatus, jqXHR) {
					self.addPageItems(data.products_list);
					self.clearBasket();
					self.add2Basket(data.items_list, false);
				}});
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
								jScrollPaneAPI = $basketScrollable.jScrollPane().data().jsp;
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
			if(conf.ajaxUrl){
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
										if(data.messages[keyMessage].TYPE == 'E') {
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
					if(cost) return getDisplayPrice(cost);
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

				this.data.price = parseFloat(this.data.price.toFixed(conf.round)); // preparation price
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
						// item
						if(items[id]>=0) return false;
						// basket
						basket.items[id].qty++;
						price = parseFloat(basket.items[id].price.toFixed(conf.round));
						basket.items[id].cost = basket.items[id].qty*price;
						from = basket.total;
						basket.total = basket.total + price;
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
						if(items[id]>=0) return false; // item

						// remove?
						if(basket.items[id].qty==1){
							if(confirm('Удалить товар "'+tmplItem.data.name+'" из корзины?')){
								self.removeBasketItem(id);
								return true;
							}else return false;
						}
						// basket
						basket.items[id].qty--;
						price = parseFloat(basket.items[id].price.toFixed(conf.round));
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
		includeDepJScrollPane();
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

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//////////////                /////////////////////////////////////////////////////////////////////////////////////
	//////////////  DEPENDENCIES  /////////////////////////////////////////////////////////////////////////////////////
	//////////////                /////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	 //////   \/////  \/////   \/////   \/////  \/////   \/////   \/////  \/////   \/////   \/////  \/////   \/////
	  ////     \///    \///     \///     \///    \///     \///     \///    \///     \///     \///    \///     \///
	   //       \/      \/       \/       \/      \/       \/       \/      \/       \/       \/      \/       \/

	var includeDepJScrollPane = function() {
		if( $.isFunction($.jScrollPane) ) {
			//return false;
		}
		/*!
		 * jScrollPane - v2.0.0beta12 - 2012-09-27
		 * http://jscrollpane.kelvinluck.com/
		 *
		 * Copyright (c) 2010 Kelvin Luck
		 * Dual licensed under the MIT or GPL licenses.
		 */

		// Script: jScrollPane - cross browser customisable scrollbars
		//
		// *Version: 2.0.0beta12, Last updated: 2012-09-27*
		//
		// Project Home - http://jscrollpane.kelvinluck.com/
		// GitHub       - http://github.com/vitch/jScrollPane
		// Source       - http://github.com/vitch/jScrollPane/raw/master/script/jquery.jscrollpane.js
		// (Minified)   - http://github.com/vitch/jScrollPane/raw/master/script/jquery.jscrollpane.min.js
		//
		// About: License
		//
		// Copyright (c) 2012 Kelvin Luck
		// Dual licensed under the MIT or GPL Version 2 licenses.
		// http://jscrollpane.kelvinluck.com/MIT-LICENSE.txt
		// http://jscrollpane.kelvinluck.com/GPL-LICENSE.txt
		//
		// About: Examples
		//
		// All examples and demos are available through the jScrollPane example site at:
		// http://jscrollpane.kelvinluck.com/
		//
		// About: Support and Testing
		//
		// This plugin is tested on the browsers below and has been found to work reliably on them. If you run
		// into a problem on one of the supported browsers then please visit the support section on the jScrollPane
		// website (http://jscrollpane.kelvinluck.com/) for more information on getting support. You are also
		// welcome to fork the project on GitHub if you can contribute a fix for a given issue.
		//
		// jQuery Versions - tested in 1.4.2+ - reported to work in 1.3.x
		// Browsers Tested - Firefox 3.6.8, Safari 5, Opera 10.6, Chrome 5.0, IE 6, 7, 8
		//
		// About: Release History
		//
		// 2.0.0beta12 - (2012-09-27) fix for jQuery 1.8+
		// 2.0.0beta11 - (2012-05-14)
		// 2.0.0beta10 - (2011-04-17) cleaner required size calculation, improved keyboard support, stickToBottom/Left, other small fixes
		// 2.0.0beta9 - (2011-01-31) new API methods, bug fixes and correct keyboard support for FF/OSX
		// 2.0.0beta8 - (2011-01-29) touchscreen support, improved keyboard support
		// 2.0.0beta7 - (2011-01-23) scroll speed consistent (thanks Aivo Paas)
		// 2.0.0beta6 - (2010-12-07) scrollToElement horizontal support
		// 2.0.0beta5 - (2010-10-18) jQuery 1.4.3 support, various bug fixes
		// 2.0.0beta4 - (2010-09-17) clickOnTrack support, bug fixes
		// 2.0.0beta3 - (2010-08-27) Horizontal mousewheel, mwheelIntent, keyboard support, bug fixes
		// 2.0.0beta2 - (2010-08-21) Bug fixes
		// 2.0.0beta1 - (2010-08-17) Rewrite to follow modern best practices and enable horizontal scrolling, initially hidden
		//							 elements and dynamically sized elements.
		// 1.x - (2006-12-31 - 2010-07-31) Initial version, hosted at googlecode, deprecated

		(function($,window,undefined){

			$.fn.jScrollPane = function(settings)
			{
				// JScrollPane "class" - public methods are available through $('selector').data('jsp')
				function JScrollPane(elem, s)
				{
					var settings, jsp = this, pane, paneWidth, paneHeight, container, contentWidth, contentHeight,
						percentInViewH, percentInViewV, isScrollableV, isScrollableH, verticalDrag, dragMaxY,
						verticalDragPosition, horizontalDrag, dragMaxX, horizontalDragPosition,
						verticalBar, verticalTrack, scrollbarWidth, verticalTrackHeight, verticalDragHeight, arrowUp, arrowDown,
						horizontalBar, horizontalTrack, horizontalTrackWidth, horizontalDragWidth, arrowLeft, arrowRight,
						reinitialiseInterval, originalPadding, originalPaddingTotalWidth, previousContentWidth,
						wasAtTop = true, wasAtLeft = true, wasAtBottom = false, wasAtRight = false,
						originalElement = elem.clone(false, false).empty(),
						mwEvent = $.fn.mwheelIntent ? 'mwheelIntent.jsp' : 'mousewheel.jsp';

					originalPadding = elem.css('paddingTop') + ' ' +
						elem.css('paddingRight') + ' ' +
						elem.css('paddingBottom') + ' ' +
						elem.css('paddingLeft');
					originalPaddingTotalWidth = (parseInt(elem.css('paddingLeft'), 10) || 0) +
						(parseInt(elem.css('paddingRight'), 10) || 0);

					function initialise(s)
					{

						var /*firstChild, lastChild, */isMaintainingPositon, lastContentX, lastContentY,
							hasContainingSpaceChanged, originalScrollTop, originalScrollLeft,
							maintainAtBottom = false, maintainAtRight = false;

						settings = s;

						if (pane === undefined) {
							originalScrollTop = elem.scrollTop();
							originalScrollLeft = elem.scrollLeft();

							elem.css(
								{
									overflow: 'hidden',
									padding: 0
								}
							);
							// TODO: Deal with where width/ height is 0 as it probably means the element is hidden and we should
							// come back to it later and check once it is unhidden...
							paneWidth = elem.innerWidth() + originalPaddingTotalWidth;
							paneHeight = elem.innerHeight();

							elem.width(paneWidth);

							pane = $('<div class="jspPane" />').css('padding', originalPadding).append(elem.children());
							container = $('<div class="jspContainer" />')
								.css({
									'width': paneWidth + 'px',
									'height': paneHeight + 'px'
								}
							).append(pane).appendTo(elem);

							/*
							 // Move any margins from the first and last children up to the container so they can still
							 // collapse with neighbouring elements as they would before jScrollPane
							 firstChild = pane.find(':first-child');
							 lastChild = pane.find(':last-child');
							 elem.css(
							 {
							 'margin-top': firstChild.css('margin-top'),
							 'margin-bottom': lastChild.css('margin-bottom')
							 }
							 );
							 firstChild.css('margin-top', 0);
							 lastChild.css('margin-bottom', 0);
							 */
						} else {
							elem.css('width', '');

							maintainAtBottom = settings.stickToBottom && isCloseToBottom();
							maintainAtRight  = settings.stickToRight  && isCloseToRight();

							hasContainingSpaceChanged = elem.innerWidth() + originalPaddingTotalWidth != paneWidth || elem.outerHeight() != paneHeight;

							if (hasContainingSpaceChanged) {
								paneWidth = elem.innerWidth() + originalPaddingTotalWidth;
								paneHeight = elem.innerHeight();
								container.css({
									width: paneWidth + 'px',
									height: paneHeight + 'px'
								});
							}

							// If nothing changed since last check...
							if (!hasContainingSpaceChanged && previousContentWidth == contentWidth && pane.outerHeight() == contentHeight) {
								elem.width(paneWidth);
								return;
							}
							previousContentWidth = contentWidth;

							pane.css('width', '');
							elem.width(paneWidth);

							container.find('>.jspVerticalBar,>.jspHorizontalBar').remove().end();
						}

						pane.css('overflow', 'auto');
						if (s.contentWidth) {
							contentWidth = s.contentWidth;
						} else {
							contentWidth = pane[0].scrollWidth;
						}
						contentHeight = pane[0].scrollHeight;
						pane.css('overflow', '');

						percentInViewH = contentWidth / paneWidth;
						percentInViewV = contentHeight / paneHeight;
						isScrollableV = percentInViewV > 1;

						isScrollableH = percentInViewH > 1;

						//onsole.log(paneWidth, paneHeight, contentWidth, contentHeight, percentInViewH, percentInViewV, isScrollableH, isScrollableV);

						if (!(isScrollableH || isScrollableV)) {
							elem.removeClass('jspScrollable');
							pane.css({
								top: 0,
								width: container.width() - originalPaddingTotalWidth
							});
							removeMousewheel();
							removeFocusHandler();
							removeKeyboardNav();
							removeClickOnTrack();
						} else {
							elem.addClass('jspScrollable');

							isMaintainingPositon = settings.maintainPosition && (verticalDragPosition || horizontalDragPosition);
							if (isMaintainingPositon) {
								lastContentX = contentPositionX();
								lastContentY = contentPositionY();
							}

							initialiseVerticalScroll();
							initialiseHorizontalScroll();
							resizeScrollbars();

							if (isMaintainingPositon) {
								scrollToX(maintainAtRight  ? (contentWidth  - paneWidth ) : lastContentX, false);
								scrollToY(maintainAtBottom ? (contentHeight - paneHeight) : lastContentY, false);
							}

							initFocusHandler();
							initMousewheel();
							initTouch();

							if (settings.enableKeyboardNavigation) {
								initKeyboardNav();
							}
							if (settings.clickOnTrack) {
								initClickOnTrack();
							}

							observeHash();
							if (settings.hijackInternalLinks) {
								hijackInternalLinks();
							}
						}

						if (settings.autoReinitialise && !reinitialiseInterval) {
							reinitialiseInterval = setInterval(
								function()
								{
									initialise(settings);
								},
								settings.autoReinitialiseDelay
							);
						} else if (!settings.autoReinitialise && reinitialiseInterval) {
							clearInterval(reinitialiseInterval);
						}

						originalScrollTop && elem.scrollTop(0) && scrollToY(originalScrollTop, false);
						originalScrollLeft && elem.scrollLeft(0) && scrollToX(originalScrollLeft, false);

						elem.trigger('jsp-initialised', [isScrollableH || isScrollableV]);
					}

					function initialiseVerticalScroll()
					{
						if (isScrollableV) {

							container.append(
								$('<div class="jspVerticalBar" />').append(
									$('<div class="jspCap jspCapTop" />'),
									$('<div class="jspTrack" />').append(
										$('<div class="jspDrag" />').append(
											$('<div class="jspDragTop" />'),
											$('<div class="jspDragBottom" />')
										)
									),
									$('<div class="jspCap jspCapBottom" />')
								)
							);

							verticalBar = container.find('>.jspVerticalBar');
							verticalTrack = verticalBar.find('>.jspTrack');
							verticalDrag = verticalTrack.find('>.jspDrag');

							if (settings.showArrows) {
								arrowUp = $('<a class="jspArrow jspArrowUp" />').bind(
									'mousedown.jsp', getArrowScroll(0, -1)
								).bind('click.jsp', nil);
								arrowDown = $('<a class="jspArrow jspArrowDown" />').bind(
									'mousedown.jsp', getArrowScroll(0, 1)
								).bind('click.jsp', nil);
								if (settings.arrowScrollOnHover) {
									arrowUp.bind('mouseover.jsp', getArrowScroll(0, -1, arrowUp));
									arrowDown.bind('mouseover.jsp', getArrowScroll(0, 1, arrowDown));
								}

								appendArrows(verticalTrack, settings.verticalArrowPositions, arrowUp, arrowDown);
							}

							verticalTrackHeight = paneHeight;
							container.find('>.jspVerticalBar>.jspCap:visible,>.jspVerticalBar>.jspArrow').each(
								function()
								{
									verticalTrackHeight -= $(this).outerHeight();
								}
							);


							verticalDrag.hover(
								function()
								{
									verticalDrag.addClass('jspHover');
								},
								function()
								{
									verticalDrag.removeClass('jspHover');
								}
							).bind(
								'mousedown.jsp',
								function(e)
								{
									// Stop IE from allowing text selection
									$('html').bind('dragstart.jsp selectstart.jsp', nil);

									verticalDrag.addClass('jspActive');

									var startY = e.pageY - verticalDrag.position().top;

									$('html').bind(
										'mousemove.jsp',
										function(e)
										{
											positionDragY(e.pageY - startY, false);
										}
									).bind('mouseup.jsp mouseleave.jsp', cancelDrag);
									return false;
								}
							);
							sizeVerticalScrollbar();
						}
					}

					function sizeVerticalScrollbar()
					{
						verticalTrack.height(verticalTrackHeight + 'px');
						verticalDragPosition = 0;
						scrollbarWidth = settings.verticalGutter + verticalTrack.outerWidth();

						// Make the pane thinner to allow for the vertical scrollbar
						pane.width(paneWidth - scrollbarWidth - originalPaddingTotalWidth);

						// Add margin to the left of the pane if scrollbars are on that side (to position
						// the scrollbar on the left or right set it's left or right property in CSS)
						try {
							if (verticalBar.position().left === 0) {
								pane.css('margin-left', scrollbarWidth + 'px');
							}
						} catch (err) {
						}
					}

					function initialiseHorizontalScroll()
					{
						if (isScrollableH) {

							container.append(
								$('<div class="jspHorizontalBar" />').append(
									$('<div class="jspCap jspCapLeft" />'),
									$('<div class="jspTrack" />').append(
										$('<div class="jspDrag" />').append(
											$('<div class="jspDragLeft" />'),
											$('<div class="jspDragRight" />')
										)
									),
									$('<div class="jspCap jspCapRight" />')
								)
							);

							horizontalBar = container.find('>.jspHorizontalBar');
							horizontalTrack = horizontalBar.find('>.jspTrack');
							horizontalDrag = horizontalTrack.find('>.jspDrag');

							if (settings.showArrows) {
								arrowLeft = $('<a class="jspArrow jspArrowLeft" />').bind(
									'mousedown.jsp', getArrowScroll(-1, 0)
								).bind('click.jsp', nil);
								arrowRight = $('<a class="jspArrow jspArrowRight" />').bind(
									'mousedown.jsp', getArrowScroll(1, 0)
								).bind('click.jsp', nil);
								if (settings.arrowScrollOnHover) {
									arrowLeft.bind('mouseover.jsp', getArrowScroll(-1, 0, arrowLeft));
									arrowRight.bind('mouseover.jsp', getArrowScroll(1, 0, arrowRight));
								}
								appendArrows(horizontalTrack, settings.horizontalArrowPositions, arrowLeft, arrowRight);
							}

							horizontalDrag.hover(
								function()
								{
									horizontalDrag.addClass('jspHover');
								},
								function()
								{
									horizontalDrag.removeClass('jspHover');
								}
							).bind(
								'mousedown.jsp',
								function(e)
								{
									// Stop IE from allowing text selection
									$('html').bind('dragstart.jsp selectstart.jsp', nil);

									horizontalDrag.addClass('jspActive');

									var startX = e.pageX - horizontalDrag.position().left;

									$('html').bind(
										'mousemove.jsp',
										function(e)
										{
											positionDragX(e.pageX - startX, false);
										}
									).bind('mouseup.jsp mouseleave.jsp', cancelDrag);
									return false;
								}
							);
							horizontalTrackWidth = container.innerWidth();
							sizeHorizontalScrollbar();
						}
					}

					function sizeHorizontalScrollbar()
					{
						container.find('>.jspHorizontalBar>.jspCap:visible,>.jspHorizontalBar>.jspArrow').each(
							function()
							{
								horizontalTrackWidth -= $(this).outerWidth();
							}
						);

						horizontalTrack.width(horizontalTrackWidth + 'px');
						horizontalDragPosition = 0;
					}

					function resizeScrollbars()
					{
						if (isScrollableH && isScrollableV) {
							var horizontalTrackHeight = horizontalTrack.outerHeight(),
								verticalTrackWidth = verticalTrack.outerWidth();
							verticalTrackHeight -= horizontalTrackHeight;
							$(horizontalBar).find('>.jspCap:visible,>.jspArrow').each(
								function()
								{
									horizontalTrackWidth += $(this).outerWidth();
								}
							);
							horizontalTrackWidth -= verticalTrackWidth;
							paneHeight -= verticalTrackWidth;
							paneWidth -= horizontalTrackHeight;
							horizontalTrack.parent().append(
								$('<div class="jspCorner" />').css('width', horizontalTrackHeight + 'px')
							);
							sizeVerticalScrollbar();
							sizeHorizontalScrollbar();
						}
						// reflow content
						if (isScrollableH) {
							pane.width((container.outerWidth() - originalPaddingTotalWidth) + 'px');
						}
						contentHeight = pane.outerHeight();
						percentInViewV = contentHeight / paneHeight;

						if (isScrollableH) {
							horizontalDragWidth = Math.ceil(1 / percentInViewH * horizontalTrackWidth);
							if (horizontalDragWidth > settings.horizontalDragMaxWidth) {
								horizontalDragWidth = settings.horizontalDragMaxWidth;
							} else if (horizontalDragWidth < settings.horizontalDragMinWidth) {
								horizontalDragWidth = settings.horizontalDragMinWidth;
							}
							horizontalDrag.width(horizontalDragWidth + 'px');
							dragMaxX = horizontalTrackWidth - horizontalDragWidth;
							_positionDragX(horizontalDragPosition); // To update the state for the arrow buttons
						}
						if (isScrollableV) {
							verticalDragHeight = Math.ceil(1 / percentInViewV * verticalTrackHeight);
							if (verticalDragHeight > settings.verticalDragMaxHeight) {
								verticalDragHeight = settings.verticalDragMaxHeight;
							} else if (verticalDragHeight < settings.verticalDragMinHeight) {
								verticalDragHeight = settings.verticalDragMinHeight;
							}
							verticalDrag.height(verticalDragHeight + 'px');
							dragMaxY = verticalTrackHeight - verticalDragHeight;
							_positionDragY(verticalDragPosition); // To update the state for the arrow buttons
						}
					}

					function appendArrows(ele, p, a1, a2)
					{
						var p1 = "before", p2 = "after", aTemp;

						// Sniff for mac... Is there a better way to determine whether the arrows would naturally appear
						// at the top or the bottom of the bar?
						if (p == "os") {
							p = /Mac/.test(navigator.platform) ? "after" : "split";
						}
						if (p == p1) {
							p2 = p;
						} else if (p == p2) {
							p1 = p;
							aTemp = a1;
							a1 = a2;
							a2 = aTemp;
						}

						ele[p1](a1)[p2](a2);
					}

					function getArrowScroll(dirX, dirY, ele)
					{
						return function()
						{
							arrowScroll(dirX, dirY, this, ele);
							this.blur();
							return false;
						};
					}

					function arrowScroll(dirX, dirY, arrow, ele)
					{
						arrow = $(arrow).addClass('jspActive');

						var eve,
							scrollTimeout,
							isFirst = true,
							doScroll = function()
							{
								if (dirX !== 0) {
									jsp.scrollByX(dirX * settings.arrowButtonSpeed);
								}
								if (dirY !== 0) {
									jsp.scrollByY(dirY * settings.arrowButtonSpeed);
								}
								scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.arrowRepeatFreq);
								isFirst = false;
							};

						doScroll();

						eve = ele ? 'mouseout.jsp' : 'mouseup.jsp';
						ele = ele || $('html');
						ele.bind(
							eve,
							function()
							{
								arrow.removeClass('jspActive');
								scrollTimeout && clearTimeout(scrollTimeout);
								scrollTimeout = null;
								ele.unbind(eve);
							}
						);
					}

					function initClickOnTrack()
					{
						removeClickOnTrack();
						if (isScrollableV) {
							verticalTrack.bind(
								'mousedown.jsp',
								function(e)
								{
									if (e.originalTarget === undefined || e.originalTarget == e.currentTarget) {
										var clickedTrack = $(this),
											offset = clickedTrack.offset(),
											direction = e.pageY - offset.top - verticalDragPosition,
											scrollTimeout,
											isFirst = true,
											doScroll = function()
											{
												var offset = clickedTrack.offset(),
													pos = e.pageY - offset.top - verticalDragHeight / 2,
													contentDragY = paneHeight * settings.scrollPagePercent,
													dragY = dragMaxY * contentDragY / (contentHeight - paneHeight);
												if (direction < 0) {
													if (verticalDragPosition - dragY > pos) {
														jsp.scrollByY(-contentDragY);
													} else {
														positionDragY(pos);
													}
												} else if (direction > 0) {
													if (verticalDragPosition + dragY < pos) {
														jsp.scrollByY(contentDragY);
													} else {
														positionDragY(pos);
													}
												} else {
													cancelClick();
													return;
												}
												scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.trackClickRepeatFreq);
												isFirst = false;
											},
											cancelClick = function()
											{
												scrollTimeout && clearTimeout(scrollTimeout);
												scrollTimeout = null;
												$(document).unbind('mouseup.jsp', cancelClick);
											};
										doScroll();
										$(document).bind('mouseup.jsp', cancelClick);
										return false;
									}
								}
							);
						}

						if (isScrollableH) {
							horizontalTrack.bind(
								'mousedown.jsp',
								function(e)
								{
									if (e.originalTarget === undefined || e.originalTarget == e.currentTarget) {
										var clickedTrack = $(this),
											offset = clickedTrack.offset(),
											direction = e.pageX - offset.left - horizontalDragPosition,
											scrollTimeout,
											isFirst = true,
											doScroll = function()
											{
												var offset = clickedTrack.offset(),
													pos = e.pageX - offset.left - horizontalDragWidth / 2,
													contentDragX = paneWidth * settings.scrollPagePercent,
													dragX = dragMaxX * contentDragX / (contentWidth - paneWidth);
												if (direction < 0) {
													if (horizontalDragPosition - dragX > pos) {
														jsp.scrollByX(-contentDragX);
													} else {
														positionDragX(pos);
													}
												} else if (direction > 0) {
													if (horizontalDragPosition + dragX < pos) {
														jsp.scrollByX(contentDragX);
													} else {
														positionDragX(pos);
													}
												} else {
													cancelClick();
													return;
												}
												scrollTimeout = setTimeout(doScroll, isFirst ? settings.initialDelay : settings.trackClickRepeatFreq);
												isFirst = false;
											},
											cancelClick = function()
											{
												scrollTimeout && clearTimeout(scrollTimeout);
												scrollTimeout = null;
												$(document).unbind('mouseup.jsp', cancelClick);
											};
										doScroll();
										$(document).bind('mouseup.jsp', cancelClick);
										return false;
									}
								}
							);
						}
					}

					function removeClickOnTrack()
					{
						if (horizontalTrack) {
							horizontalTrack.unbind('mousedown.jsp');
						}
						if (verticalTrack) {
							verticalTrack.unbind('mousedown.jsp');
						}
					}

					function cancelDrag()
					{
						$('html').unbind('dragstart.jsp selectstart.jsp mousemove.jsp mouseup.jsp mouseleave.jsp');

						if (verticalDrag) {
							verticalDrag.removeClass('jspActive');
						}
						if (horizontalDrag) {
							horizontalDrag.removeClass('jspActive');
						}
					}

					function positionDragY(destY, animate)
					{
						if (!isScrollableV) {
							return;
						}
						if (destY < 0) {
							destY = 0;
						} else if (destY > dragMaxY) {
							destY = dragMaxY;
						}

						// can't just check if(animate) because false is a valid value that could be passed in...
						if (animate === undefined) {
							animate = settings.animateScroll;
						}
						if (animate) {
							jsp.animate(verticalDrag, 'top', destY,	_positionDragY);
						} else {
							verticalDrag.css('top', destY);
							_positionDragY(destY);
						}

					}

					function _positionDragY(destY)
					{
						if (destY === undefined) {
							destY = verticalDrag.position().top;
						}

						container.scrollTop(0);
						verticalDragPosition = destY;

						var isAtTop = verticalDragPosition === 0,
							isAtBottom = verticalDragPosition == dragMaxY,
							percentScrolled = destY/ dragMaxY,
							destTop = -percentScrolled * (contentHeight - paneHeight);

						if (wasAtTop != isAtTop || wasAtBottom != isAtBottom) {
							wasAtTop = isAtTop;
							wasAtBottom = isAtBottom;
							elem.trigger('jsp-arrow-change', [wasAtTop, wasAtBottom, wasAtLeft, wasAtRight]);
						}

						updateVerticalArrows(isAtTop, isAtBottom);
						pane.css('top', destTop);
						elem.trigger('jsp-scroll-y', [-destTop, isAtTop, isAtBottom]).trigger('scroll');
					}

					function positionDragX(destX, animate)
					{
						if (!isScrollableH) {
							return;
						}
						if (destX < 0) {
							destX = 0;
						} else if (destX > dragMaxX) {
							destX = dragMaxX;
						}

						if (animate === undefined) {
							animate = settings.animateScroll;
						}
						if (animate) {
							jsp.animate(horizontalDrag, 'left', destX,	_positionDragX);
						} else {
							horizontalDrag.css('left', destX);
							_positionDragX(destX);
						}
					}

					function _positionDragX(destX)
					{
						if (destX === undefined) {
							destX = horizontalDrag.position().left;
						}

						container.scrollTop(0);
						horizontalDragPosition = destX;

						var isAtLeft = horizontalDragPosition === 0,
							isAtRight = horizontalDragPosition == dragMaxX,
							percentScrolled = destX / dragMaxX,
							destLeft = -percentScrolled * (contentWidth - paneWidth);

						if (wasAtLeft != isAtLeft || wasAtRight != isAtRight) {
							wasAtLeft = isAtLeft;
							wasAtRight = isAtRight;
							elem.trigger('jsp-arrow-change', [wasAtTop, wasAtBottom, wasAtLeft, wasAtRight]);
						}

						updateHorizontalArrows(isAtLeft, isAtRight);
						pane.css('left', destLeft);
						elem.trigger('jsp-scroll-x', [-destLeft, isAtLeft, isAtRight]).trigger('scroll');
					}

					function updateVerticalArrows(isAtTop, isAtBottom)
					{
						if (settings.showArrows) {
							arrowUp[isAtTop ? 'addClass' : 'removeClass']('jspDisabled');
							arrowDown[isAtBottom ? 'addClass' : 'removeClass']('jspDisabled');
						}
					}

					function updateHorizontalArrows(isAtLeft, isAtRight)
					{
						if (settings.showArrows) {
							arrowLeft[isAtLeft ? 'addClass' : 'removeClass']('jspDisabled');
							arrowRight[isAtRight ? 'addClass' : 'removeClass']('jspDisabled');
						}
					}

					function scrollToY(destY, animate)
					{
						var percentScrolled = destY / (contentHeight - paneHeight);
						positionDragY(percentScrolled * dragMaxY, animate);
					}

					function scrollToX(destX, animate)
					{
						var percentScrolled = destX / (contentWidth - paneWidth);
						positionDragX(percentScrolled * dragMaxX, animate);
					}

					function scrollToElement(ele, stickToTop, animate)
					{
						var e, eleHeight, eleWidth, eleTop = 0, eleLeft = 0, viewportTop, viewportLeft, maxVisibleEleTop, maxVisibleEleLeft, destY, destX;

						// Legal hash values aren't necessarily legal jQuery selectors so we need to catch any
						// errors from the lookup...
						try {
							e = $(ele);
						} catch (err) {
							return;
						}
						eleHeight = e.outerHeight();
						eleWidth= e.outerWidth();

						container.scrollTop(0);
						container.scrollLeft(0);

						// loop through parents adding the offset top of any elements that are relatively positioned between
						// the focused element and the jspPane so we can get the true distance from the top
						// of the focused element to the top of the scrollpane...
						while (!e.is('.jspPane')) {
							eleTop += e.position().top;
							eleLeft += e.position().left;
							e = e.offsetParent();
							if (/^body|html$/i.test(e[0].nodeName)) {
								// we ended up too high in the document structure. Quit!
								return;
							}
						}

						viewportTop = contentPositionY();
						maxVisibleEleTop = viewportTop + paneHeight;
						if (eleTop < viewportTop || stickToTop) { // element is above viewport
							destY = eleTop - settings.verticalGutter;
						} else if (eleTop + eleHeight > maxVisibleEleTop) { // element is below viewport
							destY = eleTop - paneHeight + eleHeight + settings.verticalGutter;
						}
						if (destY) {
							scrollToY(destY, animate);
						}

						viewportLeft = contentPositionX();
						maxVisibleEleLeft = viewportLeft + paneWidth;
						if (eleLeft < viewportLeft || stickToTop) { // element is to the left of viewport
							destX = eleLeft - settings.horizontalGutter;
						} else if (eleLeft + eleWidth > maxVisibleEleLeft) { // element is to the right viewport
							destX = eleLeft - paneWidth + eleWidth + settings.horizontalGutter;
						}
						if (destX) {
							scrollToX(destX, animate);
						}

					}

					function contentPositionX()
					{
						return -pane.position().left;
					}

					function contentPositionY()
					{
						return -pane.position().top;
					}

					function isCloseToBottom()
					{
						var scrollableHeight = contentHeight - paneHeight;
						return (scrollableHeight > 20) && (scrollableHeight - contentPositionY() < 10);
					}

					function isCloseToRight()
					{
						var scrollableWidth = contentWidth - paneWidth;
						return (scrollableWidth > 20) && (scrollableWidth - contentPositionX() < 10);
					}

					function initMousewheel()
					{
						container.unbind(mwEvent).bind(
							mwEvent,
							function (event, delta, deltaX, deltaY) {
								var dX = horizontalDragPosition, dY = verticalDragPosition;
								jsp.scrollBy(deltaX * settings.mouseWheelSpeed, -deltaY * settings.mouseWheelSpeed, false);
								// return true if there was no movement so rest of screen can scroll
								return dX == horizontalDragPosition && dY == verticalDragPosition;
							}
						);
					}

					function removeMousewheel()
					{
						container.unbind(mwEvent);
					}

					function nil()
					{
						return false;
					}

					function initFocusHandler()
					{
						pane.find(':input,a').unbind('focus.jsp').bind(
							'focus.jsp',
							function(e)
							{
								scrollToElement(e.target, false);
							}
						);
					}

					function removeFocusHandler()
					{
						pane.find(':input,a').unbind('focus.jsp');
					}

					function initKeyboardNav()
					{
						var keyDown, elementHasScrolled, validParents = [];
						isScrollableH && validParents.push(horizontalBar[0]);
						isScrollableV && validParents.push(verticalBar[0]);

						// IE also focuses elements that don't have tabindex set.
						pane.focus(
							function()
							{
								elem.focus();
							}
						);

						elem.attr('tabindex', 0)
							.unbind('keydown.jsp keypress.jsp')
							.bind(
							'keydown.jsp',
							function(e)
							{
								if (e.target !== this && !(validParents.length && $(e.target).closest(validParents).length)){
									return;
								}
								var dX = horizontalDragPosition, dY = verticalDragPosition;
								switch(e.keyCode) {
									case 40: // down
									case 38: // up
									case 34: // page down
									case 32: // space
									case 33: // page up
									case 39: // right
									case 37: // left
										keyDown = e.keyCode;
										keyDownHandler();
										break;
									case 35: // end
										scrollToY(contentHeight - paneHeight);
										keyDown = null;
										break;
									case 36: // home
										scrollToY(0);
										keyDown = null;
										break;
								}

								elementHasScrolled = e.keyCode == keyDown && dX != horizontalDragPosition || dY != verticalDragPosition;
								return !elementHasScrolled;
							}
						).bind(
							'keypress.jsp', // For FF/ OSX so that we can cancel the repeat key presses if the JSP scrolls...
							function(e)
							{
								if (e.keyCode == keyDown) {
									keyDownHandler();
								}
								return !elementHasScrolled;
							}
						);

						if (settings.hideFocus) {
							elem.css('outline', 'none');
							if ('hideFocus' in container[0]){
								elem.attr('hideFocus', true);
							}
						} else {
							elem.css('outline', '');
							if ('hideFocus' in container[0]){
								elem.attr('hideFocus', false);
							}
						}

						function keyDownHandler()
						{
							var dX = horizontalDragPosition, dY = verticalDragPosition;
							switch(keyDown) {
								case 40: // down
									jsp.scrollByY(settings.keyboardSpeed, false);
									break;
								case 38: // up
									jsp.scrollByY(-settings.keyboardSpeed, false);
									break;
								case 34: // page down
								case 32: // space
									jsp.scrollByY(paneHeight * settings.scrollPagePercent, false);
									break;
								case 33: // page up
									jsp.scrollByY(-paneHeight * settings.scrollPagePercent, false);
									break;
								case 39: // right
									jsp.scrollByX(settings.keyboardSpeed, false);
									break;
								case 37: // left
									jsp.scrollByX(-settings.keyboardSpeed, false);
									break;
							}

							elementHasScrolled = dX != horizontalDragPosition || dY != verticalDragPosition;
							return elementHasScrolled;
						}
					}

					function removeKeyboardNav()
					{
						elem.attr('tabindex', '-1')
							.removeAttr('tabindex')
							.unbind('keydown.jsp keypress.jsp');
					}

					function observeHash()
					{
						if (location.hash && location.hash.length > 1) {
							var e,
								retryInt,
								hash = escape(location.hash.substr(1)) // hash must be escaped to prevent XSS
								;
							try {
								e = $('#' + hash + ', a[name="' + hash + '"]');
							} catch (err) {
								return;
							}

							if (e.length && pane.find(hash)) {
								// nasty workaround but it appears to take a little while before the hash has done its thing
								// to the rendered page so we just wait until the container's scrollTop has been messed up.
								if (container.scrollTop() === 0) {
									retryInt = setInterval(
										function()
										{
											if (container.scrollTop() > 0) {
												scrollToElement(e, true);
												$(document).scrollTop(container.position().top);
												clearInterval(retryInt);
											}
										},
										50
									);
								} else {
									scrollToElement(e, true);
									$(document).scrollTop(container.position().top);
								}
							}
						}
					}

					function hijackInternalLinks()
					{
						// only register the link handler once
						if ($(document.body).data('jspHijack')) {
							return;
						}

						// remember that the handler was bound
						$(document.body).data('jspHijack', true);

						// use live handler to also capture newly created links
						$(document.body).delegate('a[href*=#]', 'click', function(event) {
							// does the link point to the same page?
							// this also takes care of cases with a <base>-Tag or Links not starting with the hash #
							// e.g. <a href="index.html#test"> when the current url already is index.html
							var href = this.href.substr(0, this.href.indexOf('#')),
								locationHref = location.href,
								hash,
								element,
								container,
								jsp,
								scrollTop,
								elementTop;
							if (location.href.indexOf('#') !== -1) {
								locationHref = location.href.substr(0, location.href.indexOf('#'));
							}
							if (href !== locationHref) {
								// the link points to another page
								return;
							}

							// check if jScrollPane should handle this click event
							hash = escape(this.href.substr(this.href.indexOf('#') + 1));

							// find the element on the page
							element;
							try {
								element = $('#' + hash + ', a[name="' + hash + '"]');
							} catch (e) {
								// hash is not a valid jQuery identifier
								return;
							}

							if (!element.length) {
								// this link does not point to an element on this page
								return;
							}

							container = element.closest('.jspScrollable');
							jsp = container.data('jsp');

							// jsp might be another jsp instance than the one, that bound this event
							// remember: this event is only bound once for all instances.
							jsp.scrollToElement(element, true);

							if (container[0].scrollIntoView) {
								// also scroll to the top of the container (if it is not visible)
								scrollTop = $(window).scrollTop();
								elementTop = element.offset().top;
								if (elementTop < scrollTop || elementTop > scrollTop + $(window).height()) {
									container[0].scrollIntoView();
								}
							}

							// jsp handled this event, prevent the browser default (scrolling :P)
							event.preventDefault();
						});
					}

					// Init touch on iPad, iPhone, iPod, Android
					function initTouch()
					{
						var startX,
							startY,
							touchStartX,
							touchStartY,
							moved,
							moving = false;

						container.unbind('touchstart.jsp touchmove.jsp touchend.jsp click.jsp-touchclick').bind(
							'touchstart.jsp',
							function(e)
							{
								var touch = e.originalEvent.touches[0];
								startX = contentPositionX();
								startY = contentPositionY();
								touchStartX = touch.pageX;
								touchStartY = touch.pageY;
								moved = false;
								moving = true;
							}
						).bind(
							'touchmove.jsp',
							function(ev)
							{
								if(!moving) {
									return;
								}

								var touchPos = ev.originalEvent.touches[0],
									dX = horizontalDragPosition, dY = verticalDragPosition;

								jsp.scrollTo(startX + touchStartX - touchPos.pageX, startY + touchStartY - touchPos.pageY);

								moved = moved || Math.abs(touchStartX - touchPos.pageX) > 5 || Math.abs(touchStartY - touchPos.pageY) > 5;

								// return true if there was no movement so rest of screen can scroll
								return dX == horizontalDragPosition && dY == verticalDragPosition;
							}
						).bind(
							'touchend.jsp',
							function(e)
							{
								moving = false;
								/*if(moved) {
								 return false;
								 }*/
							}
						).bind(
							'click.jsp-touchclick',
							function(e)
							{
								if(moved) {
									moved = false;
									return false;
								}
							}
						);
					}

					function destroy(){
						var currentY = contentPositionY(),
							currentX = contentPositionX();
						elem.removeClass('jspScrollable').unbind('.jsp');
						elem.replaceWith(originalElement.append(pane.children()));
						originalElement.scrollTop(currentY);
						originalElement.scrollLeft(currentX);

						// clear reinitialize timer if active
						if (reinitialiseInterval) {
							clearInterval(reinitialiseInterval);
						}
					}

					// Public API
					$.extend(
						jsp,
						{
							// Reinitialises the scroll pane (if it's internal dimensions have changed since the last time it
							// was initialised). The settings object which is passed in will override any settings from the
							// previous time it was initialised - if you don't pass any settings then the ones from the previous
							// initialisation will be used.
							reinitialise: function(s)
							{
								s = $.extend({}, settings, s);
								initialise(s);
							},
							// Scrolls the specified element (a jQuery object, DOM node or jQuery selector string) into view so
							// that it can be seen within the viewport. If stickToTop is true then the element will appear at
							// the top of the viewport, if it is false then the viewport will scroll as little as possible to
							// show the element. You can also specify if you want animation to occur. If you don't provide this
							// argument then the animateScroll value from the settings object is used instead.
							scrollToElement: function(ele, stickToTop, animate)
							{
								scrollToElement(ele, stickToTop, animate);
							},
							// Scrolls the pane so that the specified co-ordinates within the content are at the top left
							// of the viewport. animate is optional and if not passed then the value of animateScroll from
							// the settings object this jScrollPane was initialised with is used.
							scrollTo: function(destX, destY, animate)
							{
								scrollToX(destX, animate);
								scrollToY(destY, animate);
							},
							// Scrolls the pane so that the specified co-ordinate within the content is at the left of the
							// viewport. animate is optional and if not passed then the value of animateScroll from the settings
							// object this jScrollPane was initialised with is used.
							scrollToX: function(destX, animate)
							{
								scrollToX(destX, animate);
							},
							// Scrolls the pane so that the specified co-ordinate within the content is at the top of the
							// viewport. animate is optional and if not passed then the value of animateScroll from the settings
							// object this jScrollPane was initialised with is used.
							scrollToY: function(destY, animate)
							{
								scrollToY(destY, animate);
							},
							// Scrolls the pane to the specified percentage of its maximum horizontal scroll position. animate
							// is optional and if not passed then the value of animateScroll from the settings object this
							// jScrollPane was initialised with is used.
							scrollToPercentX: function(destPercentX, animate)
							{
								scrollToX(destPercentX * (contentWidth - paneWidth), animate);
							},
							// Scrolls the pane to the specified percentage of its maximum vertical scroll position. animate
							// is optional and if not passed then the value of animateScroll from the settings object this
							// jScrollPane was initialised with is used.
							scrollToPercentY: function(destPercentY, animate)
							{
								scrollToY(destPercentY * (contentHeight - paneHeight), animate);
							},
							// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
							// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
							scrollBy: function(deltaX, deltaY, animate)
							{
								jsp.scrollByX(deltaX, animate);
								jsp.scrollByY(deltaY, animate);
							},
							// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
							// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
							scrollByX: function(deltaX, animate)
							{
								var destX = contentPositionX() + Math[deltaX<0 ? 'floor' : 'ceil'](deltaX),
									percentScrolled = destX / (contentWidth - paneWidth);
								positionDragX(percentScrolled * dragMaxX, animate);
							},
							// Scrolls the pane by the specified amount of pixels. animate is optional and if not passed then
							// the value of animateScroll from the settings object this jScrollPane was initialised with is used.
							scrollByY: function(deltaY, animate)
							{
								var destY = contentPositionY() + Math[deltaY<0 ? 'floor' : 'ceil'](deltaY),
									percentScrolled = destY / (contentHeight - paneHeight);
								positionDragY(percentScrolled * dragMaxY, animate);
							},
							// Positions the horizontal drag at the specified x position (and updates the viewport to reflect
							// this). animate is optional and if not passed then the value of animateScroll from the settings
							// object this jScrollPane was initialised with is used.
							positionDragX: function(x, animate)
							{
								positionDragX(x, animate);
							},
							// Positions the vertical drag at the specified y position (and updates the viewport to reflect
							// this). animate is optional and if not passed then the value of animateScroll from the settings
							// object this jScrollPane was initialised with is used.
							positionDragY: function(y, animate)
							{
								positionDragY(y, animate);
							},
							// This method is called when jScrollPane is trying to animate to a new position. You can override
							// it if you want to provide advanced animation functionality. It is passed the following arguments:
							//  * ele          - the element whose position is being animated
							//  * prop         - the property that is being animated
							//  * value        - the value it's being animated to
							//  * stepCallback - a function that you must execute each time you update the value of the property
							// You can use the default implementation (below) as a starting point for your own implementation.
							animate: function(ele, prop, value, stepCallback)
							{
								var params = {};
								params[prop] = value;
								ele.animate(
									params,
									{
										'duration'	: settings.animateDuration,
										'easing'	: settings.animateEase,
										'queue'		: false,
										'step'		: stepCallback
									}
								);
							},
							// Returns the current x position of the viewport with regards to the content pane.
							getContentPositionX: function()
							{
								return contentPositionX();
							},
							// Returns the current y position of the viewport with regards to the content pane.
							getContentPositionY: function()
							{
								return contentPositionY();
							},
							// Returns the width of the content within the scroll pane.
							getContentWidth: function()
							{
								return contentWidth;
							},
							// Returns the height of the content within the scroll pane.
							getContentHeight: function()
							{
								return contentHeight;
							},
							// Returns the horizontal position of the viewport within the pane content.
							getPercentScrolledX: function()
							{
								return contentPositionX() / (contentWidth - paneWidth);
							},
							// Returns the vertical position of the viewport within the pane content.
							getPercentScrolledY: function()
							{
								return contentPositionY() / (contentHeight - paneHeight);
							},
							// Returns whether or not this scrollpane has a horizontal scrollbar.
							getIsScrollableH: function()
							{
								return isScrollableH;
							},
							// Returns whether or not this scrollpane has a vertical scrollbar.
							getIsScrollableV: function()
							{
								return isScrollableV;
							},
							// Gets a reference to the content pane. It is important that you use this method if you want to
							// edit the content of your jScrollPane as if you access the element directly then you may have some
							// problems (as your original element has had additional elements for the scrollbars etc added into
							// it).
							getContentPane: function()
							{
								return pane;
							},
							// Scrolls this jScrollPane down as far as it can currently scroll. If animate isn't passed then the
							// animateScroll value from settings is used instead.
							scrollToBottom: function(animate)
							{
								positionDragY(dragMaxY, animate);
							},
							// Hijacks the links on the page which link to content inside the scrollpane. If you have changed
							// the content of your page (e.g. via AJAX) and want to make sure any new anchor links to the
							// contents of your scroll pane will work then call this function.
							hijackInternalLinks: $.noop,
							// Removes the jScrollPane and returns the page to the state it was in before jScrollPane was
							// initialised.
							destroy: function()
							{
								destroy();
							}
						}
					);

					initialise(s);
				}

				// Pluginifying code...
				settings = $.extend({}, $.fn.jScrollPane.defaults, settings);

				// Apply default speed
				$.each(['mouseWheelSpeed', 'arrowButtonSpeed', 'trackClickSpeed', 'keyboardSpeed'], function() {
					settings[this] = settings[this] || settings.speed;
				});

				return this.each(
					function()
					{
						var elem = $(this), jspApi = elem.data('jsp');
						if (jspApi) {
							jspApi.reinitialise(settings);
						} else {
							$("script",elem).filter('[type="text/javascript"],:not([type])').remove();
							jspApi = new JScrollPane(elem, settings);
							elem.data('jsp', jspApi);
						}
					}
				);
			};

			$.fn.jScrollPane.defaults = {
				showArrows					: false,
				maintainPosition			: true,
				stickToBottom				: false,
				stickToRight				: false,
				clickOnTrack				: true,
				autoReinitialise			: false,
				autoReinitialiseDelay		: 500,
				verticalDragMinHeight		: 0,
				verticalDragMaxHeight		: 99999,
				horizontalDragMinWidth		: 0,
				horizontalDragMaxWidth		: 99999,
				contentWidth				: undefined,
				animateScroll				: false,
				animateDuration				: 300,
				animateEase					: 'linear',
				hijackInternalLinks			: false,
				verticalGutter				: 4,
				horizontalGutter			: 4,
				mouseWheelSpeed				: 0,
				arrowButtonSpeed			: 0,
				arrowRepeatFreq				: 50,
				arrowScrollOnHover			: false,
				trackClickSpeed				: 0,
				trackClickRepeatFreq		: 70,
				verticalArrowPositions		: 'split',
				horizontalArrowPositions	: 'split',
				enableKeyboardNavigation	: true,
				hideFocus					: false,
				keyboardSpeed				: 0,
				initialDelay                : 300,        // Delay before starting repeating
				speed						: 30,		// Default speed when others falsey
				scrollPagePercent			: .8		// Percent of visible area scrolled when pageUp/Down or track area pressed
			};

		})(jQuery,this);

	};

})(jQuery);
