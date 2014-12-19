<?php

require '../../lib/require.php';

require '../../src/Nymph.php';
\SciActive\R::_('NymphConfig', array(), function(){
	return include '../config.php';
});

$NymphREST = \SciActive\R::_('NymphREST');

require 'Game.php';

$newGame = new Game();

if (is_numeric($_REQUEST['difficulty']) && (int)$_REQUEST['difficulty'] >= 1 && (int)$_REQUEST['difficulty'] <= 3)
	$newGame->difficulty = (int)$_REQUEST['difficulty'];

$time = microtime(true);
$newGame->generateBoard();
$timeTaken = round(microtime(true) - $time, 3);

$time = microtime(true);
$newGame->makeItFun();
$timeTakenFun = round(microtime(true) - $time, 3);

$optionDistribution = $newGame->optionDistribution(true);
$optionGrid = $optionDistribution['grid'];
$optionCoords = $optionDistribution['coords'];
ksort($optionCoords);

?>
<!DOCTYPE html>
<html>
<head>
	<title>Nymph Sudoku Test</title>
</head>
<body>
	<h1>Nymph Sudoku Test</h1>
	<p>
		Set difficulty: <a href="?difficulty=1">easy</a> : <a href="?difficulty=2">medium</a> : <a href="?difficulty=3">hard</a>
	</p>
	<p>It took the Game class <?php echo $timeTaken; ?> seconds to generate this board:</p>
	<div>
		<table border="2">
			<tbody>
				<?php foreach ($newGame->solvedBoard as $y => $curRow): ?>
				<tr>
					<?php foreach ($curRow as $x => $curVal): ?>
					<td style="width: 65px; height: 65px; text-align: center;"><?php echo "<small>($x, $y) =></small><br><strong>$curVal</strong>"; ?></td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<p>It took <?php echo $timeTakenFun; ?> seconds to make it fun on <?php echo $newGame->difficulty == 1 ? 'easy' : ($newGame->difficulty == 2 ? 'medium' : 'hard'); ?> difficulty:</p>
	<div>
		<?php foreach ($optionCoords as $key => $squares): ?>
		<code style="white-space: pre;"><?php echo count($squares) < 10 ? ' '.count($squares) : count($squares); ?> square<?php echo count($squares) > 1 ? 's' : ' '; ?> with <?php echo $key; ?> possible values.</code><br>
		<?php endforeach; ?>
	</div><br>
	<div>
		<table border="2">
			<tbody>
				<?php foreach ($newGame->board as $y => $curRow): ?>
				<tr>
					<?php foreach ($curRow as $x => $curVal): ?>
					<td style="width: 65px; height: 65px; text-align: center;"><?php echo $curVal ? "<strong>$curVal</strong>" : "<small>{$optionGrid[$y][$x]} option".($optionGrid[$y][$x] > 1 ? 's' : '')."</small>"; ?></td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</body>
</html>