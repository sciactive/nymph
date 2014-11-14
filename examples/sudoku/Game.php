<?php
/**
 * @property string $name The game's text.
 * @property int $difficulty Game's difficulty from 1-3, 1 being easiest.
 * @property array $board The game board.
 * @property array $solvedBoard The complete game board.
 * @property array $playBoard The game board after squares are removed.
 * @property int $time The time the user has spent playing, in seconds.
 * @property bool $done Whether it's done.
 */
class Game extends Entity {
	const etype = 'game';
	public $clientEnabledMethods = array('generateBoard', 'makeItFun');

	public function __construct($id = 0) {
		$this->addTag('game');
		$this->difficulty = 1;
		// In the board, if a value is an integer, that means it was preset by
		// the game. If it's a string, that means it was provided by the user.
		$this->board = array(
			0 => array(),
			1 => array(),
			2 => array(),
			3 => array(),
			4 => array(),
			5 => array(),
			6 => array(),
			7 => array(),
			8 => array(),
		);
		$this->time = 0;
		$this->done = false;
		parent::__construct($id);
	}

	public function info($type) {
		if ($type == 'name' && isset($this->name))
			return $this->name;
		elseif ($type == 'type')
			return 'game';
		elseif ($type == 'types')
			return 'games';
		return null;
	}

	public function save() {
		// Do some validation.
		$this->tags = array('game');
		$allVars = array_merge(array_keys($this->data), array_keys($this->sdata));
		$allowedVars = array(
			'name',
			'cdate',
			'mdate',
			'difficulty',
			'board',
			'solvedBoard',
			'playBoard',
			'time',
			'done',
		);
		$diff = array_diff($allVars, $allowedVars);
		if ($diff) {
			throw new EntityInvalidDataException();
		}
		return parent::save();
	}

	public function generateBoard() {
		// Since we know there's nothing on the board, we can at least fill in
		// one row randomly.
		$firstRow = range(1, 9);
		shuffle($firstRow);
		for ($x = 0; $x <= 8; $x++) {
			$this->board[0][$x] = $firstRow[$x];
		}
		$firstBlockAffinity = array($this->board[0][6], $this->board[0][7], $this->board[0][8]);
		$secondBlockAffinity = array($this->board[0][0], $this->board[0][1], $this->board[0][2]);
		$thirdBlockAffinity = array($this->board[0][3], $this->board[0][4], $this->board[0][5]);

		// Oh there has to be a better way to do this, but in the interest of
		// time, I'm basically going to brute force a board together.
		for ($y = 1; $y <= 8; $y++) {
			$rowAttemts = 0;
			for ($x = 0; $x <= 8; $x++) {
				$options = $this->optionsLeft($x, $y);
				// Let's find our affinity.
				$affinities = array($firstBlockAffinity, $secondBlockAffinity, $thirdBlockAffinity);
				switch ($x) {
					case 0:
					case 1:
					case 2:
						$affinity = $affinities[$x % 3];
						break;
					case 3:
					case 4:
					case 5:
						$affinity = $affinities[($x % 3 + 1) % 3];
						break;
					case 6:
					case 7:
					case 8:
						$affinity = $affinities[($x % 3 + 2) % 3];
						break;
				}
				$affinityOptions = array_intersect($affinity, $options);
				// If we can use a value from our affinity values, let's use it.
				if ($affinityOptions)
					$options = $affinityOptions;

				// Do we have options?
				if (!$options) {
					$rowAttemts++;
					// If we've been going at it for a while, just give up and
					// try again.
					if ($rowAttemts > 15) {
						$this->board = array(0 => array(),1 => array(),2 => array(),3 => array(),4 => array(),5 => array(),6 => array(),7 => array(),8 => array());
						return $this->generateBoard();
					}
					$this->board[$y] = array();
					$x = -1;
					continue;
				}

				$this->board[$y][$x] = $options[array_rand($options)];
			}
			$firstBlockAffinity = array($this->board[$y][6], $this->board[$y][7], $this->board[$y][8]);
			$secondBlockAffinity = array($this->board[$y][0], $this->board[$y][1], $this->board[$y][2]);
			$thirdBlockAffinity = array($this->board[$y][3], $this->board[$y][4], $this->board[$y][5]);
		}

		// Cool, our board is done. Now let's keep the solved board.
		$this->solvedBoard = $this->board;
	}

	public function makeItFun() {
		$removed = 0;

		// More squares will be removed for higher difficulties.
		switch ($this->difficulty) {
			case 1:
				$remove = 45;
				$randoCount = 15;
				break;
			case 2:
				$remove = 50;
				$randoCount = 25;
				break;
			case 3:
				$remove = 55;
				$randoCount = 30;
				break;
		}

		// First, let's start by removing 20 random squares.
		$randos = array();
		while (count($randos) < $randoCount) {
			$newRando = array(rand(0, 8), rand(0, 8));
			if (!in_array($newRando, $randos))
				$randos[] = $newRando;
		}
		foreach ($randos as $curRemove) {
			$this->board[$curRemove[1]][$curRemove[0]] = null;
			$removed++;
		}

		// Now we remove up to 5 at a time, from the
		// - low count options for easy.
		// - mid count options for medium.
		// - high count options for hard.
		while ($removed < $remove) { // Based on
			$optionDistribution = $this->optionDistribution();
			$coords = $optionDistribution['coords'];
			$counts = array_keys($coords);
			switch ($this->difficulty) {
				case 1:
					$key = min($counts);
					break;
				case 2:
					rsort($counts);
					$middle = round(count($counts) / 2);
					$key = $counts[$middle-1];
					break;
				case 3:
					$key = max($counts);
					break;
			}

			if (count($coords[$key]) > 5) {
				$keys = array_rand($coords[$key], 5);
			} else {
				$keys = array_keys($coords[$key]);
			}
			foreach ($keys as $curKey) {
				$curRemove = $coords[$key][$curKey];
				$this->board[$curRemove[1]][$curRemove[0]] = null;
				$removed++;
				if ($removed >= $remove)
					break;
			}
		}

		// Now that we have it done, let's keep the original play board before
		// the user adds squares.
		$this->playBoard = $this->board;
	}

	public function optionDistribution($emptySquares = false) {
		$counts = array();
		$grid = array(0 => array(),1 => array(),2 => array(),3 => array(),4 => array(),5 => array(),6 => array(),7 => array(),8 => array());

		for ($y = 0; $y <= 8; $y++) {
			for ($x = 0; $x <= 8; $x++) {
				if (!$emptySquares && !isset($this->board[$y][$x]))
					continue;
				if ($emptySquares && isset($this->board[$y][$x]))
					continue;
				$count = count($this->optionsLeft($x, $y));
				$grid[$y][$x] = $count;
				$counts[$count] or $counts[$count] = array();
				$counts[$count][] = array($x, $y);
			}
		}

		return array('grid' => $grid, 'coords' => $counts);
	}

	private function optionsLeft($x, $y) {
		$taken = array_merge($this->neighborsX($x, $y), $this->neighborsY($x, $y), $this->neighborsSquare($x, $y));
		$notTaken = array_diff(array(1, 2, 3, 4, 5, 6, 7, 8, 9), $taken); // Not calling range() because functions are costly in PHP.
		return $notTaken;
	}

	private function neighborsY($x, $y) {
		$results = array();
		for ($y2 = 0; $y2 <= 8; $y2++) {
			if ($y === $y2)
				continue;
			if (isset($this->board[$y2][$x]))
				$results[] = $this->board[$y2][$x];
		}
		return $results;
	}
	private function neighborsX($x, $y) {
		$results = array();
		for ($x2 = 0; $x2 <= 8; $x2++) {
			if ($x === $x2)
				continue;
			if (isset($this->board[$y][$x2]))
				$results[] = $this->board[$y][$x2];
		}
		return $results;
	}
	private function neighborsSquare($x, $y) {
		$results = array();
		$minX = $y - ($y % 3);
		$minY = $x - ($x % 3);
		for ($y2 = $minX; $y2 <= $minX+2; $y2++) {
			for ($x2 = $minY; $x2 <= $minY+2; $x2++) {
				if ($y2 === $y && $x2 === $x)
					continue;
				if (isset($this->board[$y2][$x2]))
					$results[] = $this->board[$y2][$x2];
			}
		}
		return $results;
	}
}
