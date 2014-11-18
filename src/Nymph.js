/*
Nymph 1.0.0beta2 nymph.io
(C) 2014 Hunter Perrin
license LGPL
*/
// Uses AMD or browser globals.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('Nymph', ['NymphOptions', 'Promise'], factory);
    } else {
        // Browser globals
        factory(NymphOptions, Promise);
    }
}(function(NymphOptions, Promise){
	var sortProperty = null,
		sortParent = null,
		sortCaseSensitive = null,
		arraySortProperty = function(a, b){
			var property = sortProperty;
			var parent = sortParent;
			var notData = property === "guid" || property === "cdate" || property === "mdate";
			if (parent !== null && ((a.data[parent] instanceof Entity && typeof (notData ? a.data[parent][property] : a.data[parent].data[property]) !== "undefined") || (b.data[parent] instanceof Entity && typeof (notData ? b.data[parent][property] : b.data[parent].data[property]) !== "undefined"))) {
				if (!sortCaseSensitive && typeof (notData ? a.data[parent][property] : a.data[parent].data[property]) === "string" && typeof (notData ? b.data[parent][property] : b.data[parent].data[property]) === "string") {
					var aprop = (notData ? a.data[parent][property] : a.data[parent].data[property]).toUpperCase();
					var bprop = (notData ? b.data[parent][property] : b.data[parent].data[property]).toUpperCase();
					if (aprop !== bprop)
						return aprop.localeCompare(bprop);
				} else {
					if ((notData ? a.data[parent][property] : a.data[parent].data[property]) > (notData ? b.data[parent][property] : b.data[parent].data[property]))
						return 1;
					if ((notData ? a.data[parent][property] : a.data[parent].data[property]) < (notData ? b.data[parent][property] : b.data[parent].data[property]))
						return -1;
				}
			}
			// If they have the same parent, order them by their own property.
			if (!sortCaseSensitive && typeof (notData ? a[property] : a.data[property]) === "string" && typeof (notData ? b[property] : b.data[property]) === "string") {
				var aprop = (notData ? a[property] : a.data[property]).toUpperCase();
				var bprop = (notData ? b[property] : b.data[property]).toUpperCase();
				return aprop.localeCompare(bprop);
			} else {
				if ((notData ? a[property] : a.data[property]) > (notData ? b[property] : b.data[property]))
					return 1;
				if ((notData ? a[property] : a.data[property]) < (notData ? b[property] : b.data[property]))
					return -1;
			}
			return 0;
		},
		map = function(arr, fn){
			var results = [];
			for (var i = 0; i < arr.length; i++)
				results.push(fn(arr[i], i));
			return results;
		},
		makeUrl = function(url, data, noSep) {
			if (!data)
				return url;
			for (var k in data) {
				if (noSep) {
					url = url+(url.length ? '&' : '');
				} else {
					url = url+(url.indexOf('?') !== -1 ? '&' : '?');
				}
				url = url+encodeURIComponent(k)+'='+encodeURIComponent(data[k]);
			}
			return url;
		},
		getAjax = function(opt){
			var request = new XMLHttpRequest();
			request.open('GET', makeUrl(opt.url, opt.data), true);

			request.onreadystatechange = function() {
				if (this.readyState === 4){
					if (this.status >= 200 && this.status < 400){
						if (opt.dataType === "json") {
							opt.success(JSON.parse(this.responseText));
						} else {
							opt.success(this.responseText);
						}
					} else {
						opt.error({status: this.status, textStatus: this.responseText});
					}
				}
			};

			request.send();
			request = null;
		},
		postputdelAjax = function(opt){
			var request = new XMLHttpRequest();
			request.open(opt.type, opt.url, true);

			request.onreadystatechange = function() {
				if (this.readyState === 4){
					if (this.status >= 200 && this.status < 400){
						if (opt.dataType === "json") {
							opt.success(JSON.parse(this.responseText));
						} else {
							opt.success(this.responseText);
						}
					} else {
						opt.error({status: this.status, textStatus: this.responseText});
					}
				}
			};

			request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
			request.send(makeUrl('', opt.data, true));
			request = null;
		};

	Nymph = {
		// The current version of Nymph.
		version: "1.0.0beta2",

		// === Class Variables ===

		restURL: null,

		// === Events ===

		init: function(NymphOptions){
			this.restURL = NymphOptions.restURL;
			return this;
		},

		// === Methods ===

		newUID: function(name){
			var that = this;
			return new Promise(function(resolve, reject){
				postputdelAjax({
					type: 'PUT',
					url: that.restURL,
					dataType: 'text',
					data: {'action': 'uid', 'data': name},
					success: function(data) {
						resolve(data);
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		getUID: function(name){
			var that = this;
			return new Promise(function(resolve, reject){
				getAjax({
					url: that.restURL,
					dataType: 'text',
					data: {'action': 'uid', 'data': name},
					success: function(data) {
						resolve(data);
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		deleteUID: function(name){
			var that = this;
			return new Promise(function(resolve, reject){
				postputdelAjax({
					type: 'DELETE',
					url: that.restURL,
					data: {'action': 'uid', 'data': name},
					success: function(data) {
						resolve(data);
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		saveEntity: function(entity){
			var that = this;
			return new Promise(function(resolve, reject){
				postputdelAjax({
					type: entity.guid == null ? 'PUT' : 'POST',
					url: that.restURL,
					dataType: 'json',
					data: {'action': 'entity', 'data': JSON.stringify(entity)},
					success: function(data) {
						if (typeof data.guid !== "undefined" && data.guid > 0) {
							resolve(entity.init(data));
						} else {
							reject({textStatus: "Server error"});
						}
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		getEntity: function(){
			var that = this, args = arguments;
			return new Promise(function(resolve, reject){
				that.getEntityData.apply(that, args).then(function(data){
					if (data != null) {
						resolve(that.initEntity(data));
					} else {
						resolve(null);
					}
				}, function(errObj){
					reject(errObj);
				});
			});
		},

		getEntityData: function(){
			var that = this,
				args = arguments;
			return new Promise(function(resolve, reject){
				getAjax({
					url: that.restURL,
					dataType: 'json',
					data: {'action': 'entity', 'data': JSON.stringify(args)},
					success: function(data) {
						if (typeof data.guid !== "undefined" && data.guid > 0) {
							resolve(data);
						} else {
							resolve(null);
						}
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		getEntities: function(){
			var that = this,
				args = arguments;
			return new Promise(function(resolve, reject){
				getAjax({
					url: that.restURL,
					dataType: 'json',
					data: {'action': 'entities', 'data': JSON.stringify(args)},
					success: function(data) {
						resolve(map(data, that.initEntity));
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		initEntity: function(entityJSON){
			var entity;
			if (typeof entityJSON.class === "string" && typeof window[entityJSON.class] !== "undefined" && typeof window[entityJSON.class].prototype.init === "function") {
				entity = new window[entityJSON.class]();
			} else if (typeof require !== 'undefined' && require('Nymph'+entityJSON.class).prototype.init === "function") {
				entity = new require('Nymph'+entityJSON.class)();
			} else {
				throw new NymphClassNotAvailableError(entityJSON.class+" class cannot be found.")
			}
			return entity.init(entityJSON);
		},

		deleteEntity: function(entity, plural){
			var that = this, cur;
			if (plural) {
				for (var i in entity) {
					cur = entity[i].toJSON();
					cur.etype = entity[i].etype;
					entity[i] = cur;
				}
			} else {
				cur = entity.toJSON();
				cur.etype = entity.etype;
				entity = cur;
			}
			return new Promise(function(resolve, reject){
				postputdelAjax({
					type: 'DELETE',
					url: that.restURL,
					dataType: 'json',
					data: {'action': plural ? 'entities' : 'entity', 'data': JSON.stringify(entity)},
					success: function(data) {
						resolve(data);
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},

		deleteEntities: function(entities){
			return this.deleteEntity(entities, true);
		},


		serverCall: function(entity, method, params) {
			var that = this;
			return new Promise(function(resolve, reject){
				postputdelAjax({
					type: 'POST',
					url: that.restURL,
					dataType: 'json',
					data: {'action': 'method', 'data': JSON.stringify({'entity': entity, 'method': method, 'params': params})},
					success: function(data) {
						resolve(data);
					},
					error: function(errObj){
						reject(errObj);
					}
				});
			});
		},


		hsort: function(array, property, parentProperty, caseSensitive, reverse) {
			// First sort by the requested property.
			this.sort(array, property, caseSensitive, reverse);
			if (typeof parentProperty === "undefined" || parentProperty === null)
				return array;

			// Now sort by children.
			var new_array = [];
			// Look for entities ready to go in order.
			var changed, pkey, ancestry, new_key;
			while (array.length) {
				changed = false;
				for (var key in array) {
					// Must break after adding one, so any following children don't go in the wrong order.
					if (
							typeof array[key].data[parentProperty] === "undefined" ||
							array[key].data[parentProperty] === null ||
							typeof array[key].data[parentProperty].inArray !== "function" ||
							!array[key].data[parentProperty].inArray(new_array.concat(array))
						) {
						// If they have no parent (or their parent isn't in the array), they go on the end.
						new_array.push(array[key]);
						array.splice(key, 1);
						changed = true;
						break;
					} else {
						// Else find the parent.
						pkey = array[key].data[parentProperty].arraySearch(new_array);
						if (pkey !== false) {
							// And insert after the parent.
							// This makes entities go to the end of the child list.
							ancestry = [array[key].data[parentProperty].guid];
							new_key = Number(pkey);
							while (
									typeof new_array[new_key + 1] !== "undefined" &&
									typeof new_array[new_key + 1].data[parentProperty] !== "undefined" &&
									new_array[new_key + 1].data[parentProperty] !== null &&
									ancestry.indexOf(new_array[new_key + 1].data[parentProperty].guid) !== -1
								) {
								ancestry.push(new_array[new_key + 1].data[parentProperty].guid);
								new_key += 1;
							}
							// Where to place the entity.
							new_key += 1;
							if (typeof new_array[new_key] !== "undefined") {
								// If it already exists, we have to splice it in.
								new_array.splice(new_key, 0, array[key]);
							} else {
								// Else just add it.
								new_array.push(array[key]);
							}
							array.splice(key, 1);
							changed = true;
							break;
						}
					}
				}
				if (!changed) {
					// If there are any unexpected errors and the array isn't changed, just stick the rest on the end.
					if (array.length) {
						new_array = new_array.concat(array);
						array = [];
					}
				}
			}
			// Now push the new array out.
			array = new_array;
			return array;
		},
		psort: function(array, property, parentProperty, caseSensitive, reverse) {
			// Sort by the requested property.
			if (typeof property !== "undefined") {
				sortProperty = property;
				sortParent = parentProperty;
				sortCaseSensitive = (caseSensitive == true);
				array.sort(arraySortProperty);
			}
			if (reverse)
				array.reverse();
			return array;
		},
		sort: function(array, property, caseSensitive, reverse) {
			// Sort by the requested property.
			if (typeof property !== "undefined") {
				sortProperty = property;
				sortParent = null;
				sortCaseSensitive = (caseSensitive == true);
				array.sort(arraySortProperty);
			}
			if (reverse)
				array.reverse();
			return array;
		}
	};

	NymphClassNotAvailableError = function(message){
		this.name = 'NymphClassNotAvailableError';
		this.message = message;
		this.stack = (new Error()).stack;
	};
	NymphClassNotAvailableError.prototype = new Error;

	return Nymph.init(NymphOptions);
}));
