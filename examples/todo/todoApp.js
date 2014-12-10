angular.module('todoApp', []).controller('TodoController', ['$scope', function($scope) {
	$scope.todos = [];
	$scope.sort = 'name';
	$scope.showArchived = false;

	$scope.getTodos = function(archived){
		Nymph.getEntities({"class": 'Todo'}, {"type": archived ? '&' : '!&', "tag": 'archived'}).then(function(todos){
			$scope.showArchived = archived;
			if (todos) {
				Nymph.sort(todos, $scope.sort);
				$scope.todos = todos;
			}
			$scope.$apply();
		});
	};
	$scope.getTodos(false);

	$scope.addTodo = function(){
		if (typeof $scope.todoText === 'undefined' || $scope.todoText === '')
			return;
		var todo = new Todo();
		todo.set('name', $scope.todoText);
		todo.save().then(function(todo){
			$scope.todos.push(todo);
			$scope.todoText = '';
			$scope.todos = Nymph.sort($scope.todos, $scope.sort);
			$scope.$apply();
		}, function(errObj){
			alert("Error: "+errObj.textStatus);
		});
	};

	$scope.sortTodos = function(){
		$scope.todos = Nymph.sort($scope.todos, $scope.sort);
		$scope.$apply();
	};

	$scope.save = function(todo){
		todo.save().then(null, function(errObj){
			alert('Error: '+errObj.textStatus);
		});
	};

	$scope.remaining = function(){
		var count = 0;
		angular.forEach($scope.todos, function(todo){
			count += todo.get('done') ? 0 : 1;
		});
		return count;
	};

	$scope.archive = function(){
		var oldTodos = $scope.todos;
		$scope.todos = [];
		angular.forEach(oldTodos, function(todo){
			if (todo.get('done')) {
				todo.archive().then(function(success){
					if (!success)
						alert("Couldn't save changes to "+todo.get('name'));
				}, function(errObj){
					alert("Error: "+errObj.textStatus+"\nCouldn't archive "+todo.get('name'));
				});
			} else {
				$scope.todos.push(todo);
			}
		});
	};

	$scope.delete = function(){
		var todos = $scope.todos;
		$scope.todos = [];
		Nymph.deleteEntities(todos).then(function(){
			$scope.getTodos(false);
		}, function(errObj){
			$scope.todos = [];
			$scope.$apply();
			alert("Error: "+errObj.textStatus+"\nCouldn't delete.");
		});
		$scope.$apply();
	};
}]);
