// Uses AMD or browser globals.
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as a module.
        define('NymphGame', ['NymphEntity'], factory);
    } else {
        // Browser globals
        factory(Entity);
    }
}(function(Entity){
	Game = function(id){
		this.constructor.call(this, id);
		this.addTag('game');
		this.data.difficulty = 1;
		this.data.board = [
			[], [], [], [], [], [], [], [], []
		];
		this.data.time = 0;
		this.data.done = false;
	};
	Game.prototype = new Entity();

	var thisClass = {
		// === The Name of the Class ===
		class: 'Game',

		// === Class Variables ===
		etype: "game",
		mistakes: [
			[], [], [], [], [], [], [], [], []
		],
		help: 1,

		// === Class Methods ===

		generateBoard: function(){
			return this.serverCall('generateBoard', arguments);
		},

		makeItFun: function(){
			return this.serverCall('makeItFun', arguments);
		},

		checkDone: function(){
			this.data.done = false;
			for (y in this.data.board) {
				for (x in this.data.board[y]) {
					if (this.data.playBoard[y][x])
						continue;
					else if (!this.data.board[y][x])
						return;
					else if (this.neighborsSquare(x, y).concat(this.neighborsY(x, y)).concat(this.neighborsX(x, y)).indexOf(this.data.board[y][x]) !== -1)
						return;
				}
			}
			this.data.done = true;
		},
		calculateErrors: function(){
			this.checkDone();
			if (this.data.done) {
				this.mistakes = [
					[], [], [], [], [], [], [], [], []
				];
				return;
			}
			switch (this.help) {
				case 1:
					// Oh, we got a badass over here.
					this.mistakes = [
						[], [], [], [], [], [], [], [], []
					];
					break;
				case 2:
					// We need to mark every spot where the user made an obvious
					// mistake.
					for (y in this.data.board) {
						for (x in this.data.board[y]) {
							if (this.data.playBoard[y][x])
								this.mistakes[y][x] = false;
							else if (this.data.board[y][x] && this.neighborsSquare(x, y).concat(this.neighborsY(x, y)).concat(this.neighborsX(x, y)).indexOf(Number(this.data.board[y][x])) !== -1)
								this.mistakes[y][x] = true;
							else {
								this.mistakes[y][x] = false;
							}
						}
					}
					break;
				case 3:
					// We need to mark every spot the user differs from the
					// solved board.
					for (y in this.data.board) {
						for (x in this.data.board[y]) {
							if (this.data.playBoard[y][x])
								this.mistakes[y][x] = false;
							else if (this.data.board[y][x] && this.data.board[y][x] !== this.data.solvedBoard[y][x])
								this.mistakes[y][x] = true;
							else
								this.mistakes[y][x] = false;
						}
					}
					break;
			}
		},
		neighborsY: function(x, y) {
			var results = [];
			for (var y2 = 0; y2 <= 8; y2++) {
				if (y == y2)
					continue;
				if (this.data.board[y2][x])
					results.push(Number(this.data.board[y2][x]));
			}
			return results;
		},
		neighborsX: function(x, y) {
			var results = [];
			for (var x2 = 0; x2 <= 8; x2++) {
				if (x == x2)
					continue;
				if (this.data.board[y][x2])
					results.push(Number(this.data.board[y][x2]));
			}
			return results;
		},
		neighborsSquare: function(x, y) {
			var results = [];
			var minX = y - (y % 3);
			var minY = x - (x % 3);
			for (var y2 = minX; y2 <= minX+2; y2++) {
				for (var x2 = minY; x2 <= minY+2; x2++) {
					if (y2 == y && x2 == x)
						continue;
					if (this.data.board[y2][x2])
						results.push(Number(this.data.board[y2][x2]));
				}
			}
			return results;
		}
	};
	for (var p in thisClass) {
		Game.prototype[p] = thisClass[p];
	}

	return Game;
}));
