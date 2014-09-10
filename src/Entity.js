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
	var arrayUnique = function(array){
		var a = array.concat();
		for(var i=0; i<a.length; ++i) {
			for(var j=i+1; j<a.length; ++j) {
				if(a[i] === a[j])
					a.splice(j--, 1);
			}
		}
		return a;
	}, onlyStrings = function(array){
		var newArray = [];
		for (var k in array) {
			if (typeof array[k] === "string") {
				newArray.push(array[k]);
			} else {
				if (typeof array[k].toString === "function") {
					newArray.push(array[k].toString());
				}
			}
		}
		return newArray;
	}, getDataReference = function(item, jqMapTest) {
		if (item instanceof Entity && typeof item.toReference === "function") {
			// Convert entities to references.
			// jQuery's map will flatten arrays, so we need to wrap it in an array if it's being called from map.
			return (typeof jqMapTest === "undefined") ? item.toReference() : [item.toReference()];
		} else if ($.isArray(item)) {
			// Recurse into lower arrays.
			return $.map(item, getDataReference);
		} else if (item instanceof Object) {
			var newObj;
			if (Object.create) {
				newObj = Object.create(item);
			} else {
				var F = function () {};
				F.prototype = item;
				newObj = new F();
			}
			for (var k in item) {
				newObj[k] = getDataReference(item[k]);
			}
		}
		// Not an entity or array, just return it.
		return item;
	}, getSleepingReference = function(item) {
		if ($.isArray(item)) {
			// Check if it's a reference.
			if (item[0] === 'nymph_entity_reference') {
				if (typeof item[2] === "string" && typeof window[item[2]] !== "undefined" && typeof window[item[2]].prototype.referenceSleep === "function") {
					var entity = new window[item[2]]();
					entity.referenceSleep(item);
					return entity;
				}
			} else {
				// Recurse into lower arrays.
				return $.map(item, getSleepingReference);
			}
		} else if (item instanceof Object) {
			for (var k in item) {
				item[k] = getSleepingReference(item[k]);
			}
		}
		// Not an array, just return it.
		return item;
	};


	Entity = function(id){
		if (typeof id !== "undefined" && !isNaN(id)) {
			Nymph.getEntity({"class":this.class},{"type":"&","guid":id}).then(function(data){
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
			if (jsonEntity == null) {
				return this;
			}

			this.isASleepingReference = false;
			this.sleepingReference = false;

			this.guid = jsonEntity.guid;
			this.cdate = jsonEntity.cdate;
			this.mdate = jsonEntity.mdate;
			this.tags = jsonEntity.tags;
			this.etype = jsonEntity.etype;
			this.info = jsonEntity.info;
			this.data = jsonEntity.data;
			for (var k in this.data) {
				this.data[k] = getSleepingReference(this.data[k]);
			}

			return this;
		},

		// === Class Methods ===

		// Tag methods.
		addTag: function(){
			var tags;
			if ($.isArray(arguments[0])) {
				tags = arguments[0];
			} else if (arguments.length) {
				tags = $.makeArray(arguments);
			}
			this.tags = onlyStrings(arrayUnique(this.tags.concat(tags)));
		},
		hasTag: function(){
			var tagArray = arguments;
			if ($.isArray(arguments[0]))
				tagArray = tagArray[0];
			for (var k in tagArray) {
				if ($.inArray(tagArray[k], this.tags) === -1)
					return false;
			}
			return true;
		},
		removeTag: function(){
			var tagArray = arguments, newTags = [];
			if ($.isArray(arguments[0]))
				tagArray = tagArray[0];
			for (var k in this.tags) {
				if ($.inArray(this.tags[k], tagArray) === -1) {
					newTags.push(this.tags[k]);
				}
			}
			this.tags = newTags;
		},

		// Property getter and setter. You can also just access Entity.data directly.
		get: function(name){
			if ($.isArray(arguments[0])) {
				var result = {};
				for (var k in name) {
					result[name[k]] = this.data[name[k]];
				}
				return result;
			} else {
				return this.data[name];
			}
		},
		set: function(name, value){
			if (typeof name === "object") {
				for (var k in name) {
					this.data[k] = name[k];
				}
			} else {
				this.data[name] = value;
			}
		},

		save: function(){
			return Nymph.saveEntity(this);
		},

		delete: function(){
			return Nymph.deleteEntity(this);
		},

		toJSON: function(){
			var obj = {};
			obj.guid = this.guid;
			obj.cdate = this.cdate;
			obj.mdate = this.mdate;
			obj.tags = $.merge([], this.tags);
			obj.etype = this.etype;
			obj.data = {};
			for (var k in this.data) {
				obj.data[k] = getDataReference(this.data[k]);
			}
			obj.class = this.class;
			return obj;
		},

		toReference: function(){
			if (this.isASleepingReference)
				return this.sleepingReference;
			if (this.guid == null)
				return this;
			return ['nymph_entity_reference', this.guid, this.class];
		},

		referenceSleep: function(reference){
			this.isASleepingReference = true;
			this.sleepingReference = reference;
		},

		ready: function(success, error){
			var that = this;
			return new Promise(function(resolve, reject){
				if (!that.isASleepingReference) {
					resolve(that);
					if (typeof success === "function")
						success(that);
				} else {
					Nymph.getEntityData({"class":that.sleepingReference[2]}, {"type":"&","guid":that.sleepingReference[1]}).then(function(data){
						resolve(that.init(data));
						if (typeof success === "function")
							success(that);
					}, function(errObj){
						reject(errObj);
						if (typeof error === "function")
							error(that);
					});
				}
			});
		}
	});

	return Entity;
}));
