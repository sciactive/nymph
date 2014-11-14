// This file is a demo class that extends the Entity class.
// Uses AMD or browser globals.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphEmployee', ['NymphEntity'], factory);
    } else {
        // Browser globals
        factory(Entity);
    }
}(function(Entity){
	Employee = function(id){
		this.constructor.call(this, id);
		this.addTag('employee');
		this.data.current = true;
		this.data.subordinates = [];
	};
	Employee.prototype = new Entity();

	var thisClass = {
		// === The Name of the Class ===
		class: 'Employee',

		// === Class Variables ===
		etype: "employee"
	};
	for (var p in thisClass) {
		Employee.prototype[p] = thisClass[p];
	}

	return Employee;
}));
