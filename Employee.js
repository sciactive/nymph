// This file is a demo class that extends the Entity class.
// Uses AMD or browser globals for jQuery.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphEmployee', ['NymphEntity'], factory);
    } else {
        // Browser globals
        factory(jQuery, Entity);
    }
}(function($, Nymph){
	Employee = function(id){
		if (typeof id !== "undefined" && !isNaN(id)) {
			Nymph.getEntity({"class":this.class},{"type":"&amp;","guid":id}).then(function(data){
				this.init(data);
			}, function(jqXHR, status){
				throw new Error();
			});
		}
	};
	$.extend(Employee.prototype, Entity.prototype, {
		// === The Name of the Class ===
		class: 'Employee',

		// === Class Variables ===
		etype: "employee"
	});

	return Employee;
}));
