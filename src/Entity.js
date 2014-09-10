/*
Nymph Entity 0.0.1alpha nymph.io
(C) 2014 Hunter Perrin
license LGPL
*/
// Uses AMD or browser globals for jQuery.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphEntity', ['jquery', 'Nymph'], factory);
    } else {
        // Browser globals
        factory(jQuery, Nymph);
    }
}(function($, Nymph){
	Entity = function(id){
		if (typeof id !== "undefined" && !isNaN(id)) {
			Nymph.getEntity({"class":this.class},{"type":"&amp;","guid":id}).then(function(data){
				this.init(data);
			}, function(jqXHR, status){
				throw new Error();
			});
		}
	};
	$.extend(Entity.prototype, {
		// The current version of Entity.
		version: "0.0.1alpha",

		// === The Name of the Class ===
		class: 'Entity',

		// === Class Variables ===

		guid: null,
		cdate: null,
		mdate: null,
		tags: [],
		etype: "entity",
		info: {},
		data: {},
		isASleepingReference: false,
		sleepingReference: false,

		// === Events ===

		init: function(jsonEntity){
			var that = this;

			this.isASleepingReference = false;
			this.sleepingReference = false;

			this.guid = jsonEntity.guid;
			this.cdate = jsonEntity.cdate;
			this.mdate = jsonEntity.mdate;
			this.tags = jsonEntity.tags;
			this.etype = jsonEntity.etype;
			this.info = jsonEntity.info;
			this.data = jsonEntity.data;

			return this;
		},

		// === Class Methods ===

		// Property getter and setter. You can also just access Entity.data directly.
		get: function(name){ return this.data[name]; },
		set: function(name, value){ this.data[name] = value; },

		save: function(){
			
		}
	});

	return Entity;
}));
