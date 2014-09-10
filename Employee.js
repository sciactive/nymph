// This file is a demo class that extends the Entity class.
// Uses AMD or browser globals for jQuery.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphEmployee', ['jquery', 'NymphEntity'], factory);
    } else {
        // Browser globals
        factory(jQuery, Entity);
    }
}(function($, Entity){
	Employee = function(id){
		if (typeof id !== "undefined" && !isNaN(id)) {
			Nymph.getEntity({"class":this.class},{"type":"&","guid":id}).then(function(data){
				this.init(data);
			}, function(jqXHR, status){
				throw new Error();
			});
		}
	};
	Employee.prototype = new Entity();

	$.extend(Employee.prototype, {
		// === The Name of the Class ===
		class: 'Employee',

		// === Class Variables ===
		etype: "employee"
	});

	return Employee;
}));
