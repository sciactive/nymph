angular.module('sudokuApp', []).controller('SudokuController', ['$scope', '$interval', function($scope, $interval) {
	$scope.uiState = {
		player: '',
		difficulty: 1,
		loading: false,
		sort: 'cdate',
		games: [],
		timeDiff: ''
	};
	$scope.curGame = null;

	$scope.calcTime = function(time){
		var hours = Math.floor(time / 3600);
		var minutes = Math.floor((time % 3600) / 60);
		var seconds = time % 60;
		return (hours ? (hours+":"+(minutes > 9 ? minutes : "0"+minutes)+":"+(seconds > 9 ? seconds : "0"+seconds)) : (minutes ? minutes+":"+(seconds > 9 ? seconds : "0"+seconds) : seconds));
	};
	$scope.printDate = function(time){
		return (new Date(time*1000)).toLocaleString();
	};

	Nymph.getEntities({"class": 'Game'}, {"type": '&', "tag": 'game'}).then(function(games){
		if (games && games.length) {
			Nymph.sort(games, $scope.uiState.sort);
			$scope.uiState.games = games;
			$scope.$apply();
		}
	});

	$scope.startNewGame = function() {
		if (typeof $scope.uiState.player === 'undefined' || $scope.uiState.player === '')
			return;
		if ([1, 2, 3].indexOf($scope.uiState.difficulty) === -1)
			return;
		var game = new Game();
		game.set({
			'name': $scope.uiState.player,
			'difficulty': $scope.uiState.difficulty
		});
		$scope.uiState.loading = "Generating a new game board...";
		game.generateBoard().then(function(){
			$scope.uiState.loading = "Applying the difficulty level...";
			$scope.$apply();
			game.makeItFun().then(function(){
				$scope.uiState.loading = "Loading the new game...";
				$scope.$apply();
				game.save().then(function(game){
					$scope.uiState.games.push(game);
					$scope.uiState.player = '';
					$scope.uiState.difficulty = 1;
					$scope.uiState.games = Nymph.sort($scope.uiState.games, $scope.uiState.sort);
					$scope.curGame = game;
					$scope.startTimer();
					$scope.uiState.loading = false;
					$scope.$apply();
				}, function(errObj){
					$scope.uiState.loading = false;
					$scope.$apply();
					alert("Error: "+errObj.textStatus);
				});
			}, function(errObj){
				$scope.uiState.loading = false;
				$scope.$apply();
				alert("Error: "+errObj.textStatus);
			});
		}, function(errObj){
			$scope.uiState.loading = false;
			$scope.$apply();
			alert("Error: "+errObj.textStatus);
		});
	};

	$scope.sortGames = function() {
		$scope.uiState.games = Nymph.sort($scope.uiState.games, $scope.uiState.sort);
	};

	$scope.saveState = function(showErr) {
		$scope.saving = true;
		$scope.curGame.save().then(function(){
			$scope.saving = false;
			$scope.$apply();
		}, function(errObj){
			$scope.saving = false;
			$scope.$apply();
			if (showErr)
				alert('Error: '+errObj.textStatus);
		});
	};

	$scope.loadGame = function(game) {
		$scope.curGame = game;
		$scope.curGame.calculateErrors();
		$scope.startTimer();
	};

	$scope.clearGame = function(){
		$scope.saveState(true);
		$scope.curGame = null;
		$scope.stopTimer();
	};

	$scope.deleteGame = function(game) {
		if (!confirm('Are you sure?'))
			return;
		var key = game.arraySearch($scope.uiState.games);
		game.delete().then(function(){
			if (key !== false)
				$scope.uiState.games.splice(key, 1);
			$scope.$apply();
		}, function(errObj){
			alert('Error: '+errObj.textStatus);
		});
	};

	var gameTimer;
	$scope.startTimer = function(){
		$scope.uiState.timeDiff = $scope.calcTime($scope.curGame.data.time);
		if (angular.isDefined(gameTimer))
			$interval.cancel(gameTimer);
		gameTimer = $interval(function(){
			if ($scope.curGame.data.done) {
				$scope.stopTimer();
				return;
			}
			$scope.curGame.data.time++;
			$scope.uiState.timeDiff = $scope.calcTime($scope.curGame.data.time);
			// Don't save too often.
			if ($scope.curGame.data.time % 10 === 0)
				$scope.curGame.save();
		}, 1000);
	};
	$scope.stopTimer = function(){
		$interval.cancel(gameTimer);
	};
}]);
