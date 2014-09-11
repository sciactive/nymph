// This file is a demo class that extends the Entity class.
// Uses AMD or browser globals for jQuery.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphTodo', ['jquery', 'NymphEntity'], factory);
    } else {
        // Browser globals
        factory(jQuery, Entity);
    }
}(function($, Entity){
	Todo = function(id){
		this.constructor.call(this, id);
		this.addTag('todo');
	};
	Todo.prototype = new Entity();

	$.extend(Todo.prototype, {
		// === The Name of the Class ===
		class: 'Todo',

		// === Class Variables ===
		etype: "todo"
	});

	return Todo;
}));
