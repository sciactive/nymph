<?php

if (getenv('DATABASE_URL')) {
	// No import/export on Heroku.
	header('HTTP/1.1 403 Forbidden', true, 403);
	echo "403 Forbidden";
	exit;
}

if ($_FILES) {
	if ($_FILES['games']['error'] === 0) {
		require '../../lib/require.php';

		require '../../src/Nymph.php';
		RPHP::_('NymphConfig', array(), function(){
			return include '../config.php';
		});

		RPHP::_(array('Nymph'), function(){
			require 'Game.php';
		});

		$result = RPHP::_('Nymph')->import($_FILES['games']['tmp_name']);
		if ($result) {
			header("HTTP/1.1 302 Found", true, 302);
			header("Location: index.php");
		}
	} else {
		$result = false;
	}
}
?>
<!doctype html>
<html>
	<head>
		<title>Import Saved Games</title>
		<meta charset="utf-8">
		<link rel="stylesheet" href="sudoku.css">
	</head>
	<body>
		<div class="container">
			<div class="page-header">
				<h1>Nymph Sudoku <small>Saved Games Import</small></h1>
			</div>
			<?php if ($_FILES) { ?>
			<p>It looks like the import <?php echo $result ? 'succeeded' : 'failed' ?>.</p>
			<?php } ?>
			<p>
				Upload a "NEX" saved games file to import:
				<form method="POST" action="" enctype="multipart/form-data">
					<input type="file" name="games">&nbsp;&nbsp;&nbsp;<input type="submit" value="Submit">
				</form>
			</p>
		</div>
	</body>
</html>
