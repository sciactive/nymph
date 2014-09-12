angular.module('todoApp', []).controller('TodoController', ['$scope', function($scope) {
	$scope.todos = [];
	Nymph.getEntities({"class": 'Todo'}, {"type": '&', "tag": 'todo', "!tag": 'archived'}).then(function(todos){
		if (todos && todos.length) {
			$scope.todos = todos;
			$scope.$apply();
		}
	});

	$scope.addTodo = function() {
		if (typeof $scope.todoText === 'undefined' || $scope.todoText === '')
			return;
		var todo = new Todo();
		todo.set('name', $scope.todoText);
		todo.save().then(function(todo){
			$scope.todos.push(todo);
			$scope.todoText = '';
			$scope.$apply();
		}, function(errObj){
			alert("Error: "+errObj.textStatus);
		});
	};

	$scope.remaining = function() {
		var count = 0;
		angular.forEach($scope.todos, function(todo) {
			count += todo.get('done') ? 0 : 1;
		});
		return count;
	};

	$scope.archive = function() {
		var oldTodos = $scope.todos;
		$scope.todos = [];
		angular.forEach(oldTodos, function(todo) {
			if (todo.get('done')) {
				todo.addTag('archived');
				todo.save().then(null, function(errObj){
					alert("Error: "+errObj.textStatus);
				});
			} else {
				$scope.todos.push(todo);
			}
		});
	};
}]);
