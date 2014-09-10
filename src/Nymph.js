/*
Nymph 0.0.1alpha nymph.io
(C) 2014 Hunter Perrin
license LGPL
*/
// Uses AMD or browser globals for jQuery.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('Nymph', ['jquery', 'NymphOptions', 'Promise'], factory);
    } else {
        // Browser globals
        factory(jQuery, NymphOptions, Promise);
    }
}(function($, NymphOptions, Promise){
	Nymph = {
		// The current version of Nymph.
		version: "0.0.1alpha",

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
				$.ajax({
					type: 'PUT',
					url: that.restURL,
					dataType: 'text',
					data: {'action': 'uid', 'data': name},
					success: function(data) {
						resolve(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						reject({jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown});
					}
				});
			});
		},

		getUID: function(name){
			var that = this;
			return new Promise(function(resolve, reject){
				$.ajax({
					type: 'GET',
					url: that.restURL,
					dataType: 'text',
					data: {'action': 'uid', 'data': name},
					success: function(data) {
						resolve(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						reject({jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown});
					}
				});
			});
		},

		deleteUID: function(name){
			var that = this;
			return new Promise(function(resolve, reject){
				$.ajax({
					type: 'DELETE',
					url: that.restURL,
					data: {'action': 'uid', 'data': name},
					success: function(data) {
						resolve(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						reject({jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown});
					}
				});
			});
		},

		getEntity: function(){
			var that = this,
				args = arguments;
			return new Promise(function(resolve, reject){
				$.ajax({
					type: 'GET',
					url: that.restURL,
					dataType: 'json',
					data: {'action': 'entity', 'data': JSON.stringify(args)},
					success: function(data) {
						if (typeof data.guid !== "undefined" && data.guid > 0) {
							var entity;
							if (typeof data.class === "string" && typeof window[data.class] !== "undefined" && typeof window[data.class].prototype.init === "function") {
								entity = new window[data.class]();
							} else {
								entity = new Entity();
							}
							resolve(entity.init(data));
						} else {
							resolve(null);
						}
					},
					error: function(jqXHR, textStatus, errorThrown){
						reject({jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown});
					}
				});
			});
		},

		getEntities: function(){
			var that = this,
				args = arguments;
			return new Promise(function(resolve, reject){
				$.ajax({
					type: 'GET',
					url: that.restURL,
					dataType: 'json',
					data: {'action': 'entities', 'data': JSON.stringify(args)},
					success: function(data) {
						resolve(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						reject({jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown});
					}
				});
			});
		},

		deleteEntity: function(entity, plural){
			var that = this;
			return new Promise(function(resolve, reject){
				$.ajax({
					type: 'DELETE',
					url: that.restURL,
					dataType: 'json',
					data: {'action': plural ? 'entities' : 'entity', 'data': JSON.stringify(entity)},
					success: function(data) {
						resolve(data);
					},
					error: function(jqXHR, textStatus, errorThrown){
						reject({jqXHR: jqXHR, textStatus: textStatus, errorThrown: errorThrown});
					}
				});
			});
		},

		deleteEntities: function(entities){
			return this.deleteEntity(entities, true);
		}
	};

	return Nymph.init(NymphOptions);
}));
