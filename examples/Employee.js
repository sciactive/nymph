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
		this.constructor.call(this, id);
		this.addTag('employee');
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
