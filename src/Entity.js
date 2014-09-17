/*
Nymph Entity 0.0.1alpha nymph.io
(C) 2014 Hunter Perrin
license LGPL
*/
// Uses AMD or browser globals for jQuery.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphEntity', ['jquery', 'Nymph', 'Promise'], factory);
    } else {
        // Browser globals
        factory(jQuery, Nymph, Promise);
    }
}(function($, Nymph, Promise){
	var sleepErr = "This entity is in a sleeping reference state. You must use .ready().then() to wake it.",
	arrayUnique = function(array){
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
				var entity;
				if (typeof item[2] === "string" && typeof window[item[2]] !== "undefined" && typeof window[item[2]].prototype.referenceSleep === "function") {
					entity = new window[item[2]]();
				} else if (typeof require !== 'undefined' && require('Nymph'+item[2]).prototype.init === "function") {
					entity = new require('Nymph'+item[2])();
				} else {
					throw new NymphClassNotAvailableError(item[2]+" class cannot be found.")
				}
				entity.referenceSleep(item);
				return entity;
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
	}, sortObj = function(obj) { // adapted from http://am.aurlien.net/post/1221493460/sorting-javascript-objects
		var temp_array = [];
		for (var key in obj) {
			if (obj.hasOwnProperty(key)) {
				temp_array.push(key);
			}
		}
		temp_array.sort();
		var temp_obj = {};
		for (var i=0; i<temp_array.length; i++) {
		  temp_obj[temp_array[i]] = obj[temp_array[i]];
		}
		return temp_obj;
	};


	Entity = function(id){
		this.guid = null;
		this.cdate = null;
		this.mdate = null;
		this.tags = [];
		this.info = {};
		this.data = {};
		this.isASleepingReference = false;
		this.sleepingReference = false;
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

		etype: "entity",

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
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			var tags;
			if ($.isArray(arguments[0])) {
				tags = arguments[0];
			} else if (arguments.length) {
				tags = $.makeArray(arguments);
			}
			this.tags = onlyStrings(arrayUnique(this.tags.concat(tags)));
		},
		hasTag: function(){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
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
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
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
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
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
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			if (typeof name === "object") {
				for (var k in name) {
					this.data[k] = name[k];
				}
			} else {
				this.data[name] = value;
			}
		},

		save: function(){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			return Nymph.saveEntity(this);
		},

		delete: function(){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			return Nymph.deleteEntity(this);
		},

		is: function(object){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			if (!(object instanceof Entity))
				return false;
			if ((this.guid && this.guid > 0) || (object.guid && object.guid > 0)) {
				return this.guid == object.guid;
			} else if (typeof object.toJSON !== 'function') {
				return false;
			} else {
				var obData = sortObj(object.toJSON());
				obData.tags.sort();
				obData.data = sortObj(obData.data);
				var myData = sortObj(this.toJSON());
				myData.tags.sort();
				myData.data = sortObj(myData.data);
				return JSON.stringify(obData) == JSON.stringify(myData);
			}
		},
		equals: function(object){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			if (!(object instanceof Entity))
				return false;
			if ((this.guid && this.guid > 0) || (object.guid && object.guid > 0)) {
				if (this.guid != object.guid)
					return false;
			}
			if (object.class != this.class)
				return false;
			//return eq(this, object, [], []);
			var obData = sortObj(object.toJSON());
			obData.tags.sort();
			obData.data = sortObj(obData.data);
			var myData = sortObj(this.toJSON());
			myData.tags.sort();
			myData.data = sortObj(myData.data);
			return JSON.stringify(obData) == JSON.stringify(myData);
		},
		inArray: function(array, strict){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			if (!$.isArray(array))
				return false;
			for (var k in array) {
				if (strict ? this.equals(array[k]) : this.is(array[k]))
					return true;
			}
			return false;
		},
		arraySearch: function(array, strict){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
			if (!$.isArray(array))
				return false;
			for (var k in array) {
				if (strict ? this.equals(array[k]) : this.is(array[k]))
					return k;
			}
			return false;
		},

		toJSON: function(){
			if (this.isASleepingReference)
				throw new EntityIsSleepingReferenceError(sleepErr);
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

	EntityIsSleepingReferenceError = function(message){
		this.name = 'EntityIsSleepingReferenceError';
		this.message = message;
		this.stack = (new Error()).stack;
	};
	EntityIsSleepingReferenceError.prototype = new Error;

	NymphClassNotAvailableError = function(message){
		this.name = 'NymphClassNotAvailableError';
		this.message = message;
		this.stack = (new Error()).stack;
	};
	NymphClassNotAvailableError.prototype = new Error;

	return Entity;
}));
