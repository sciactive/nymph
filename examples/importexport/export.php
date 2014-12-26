<?php

if (getenv('DATABASE_URL')) {
	// No import/export on Heroku.
	header('HTTP/1.1 403 Forbidden', true, 403);
	echo "403 Forbidden";
	exit;
}

require '../../lib/require.php';

require '../../src/autoload.php';
\SciActive\R::_('NymphConfig', [], function(){
	return include '../config.php';
});

\SciActive\R::_(['Nymph'], function(){
	require '../employee/Employee.php';
	require '../sudoku/Game.php';
	require '../todo/Todo.php';
});

\Nymph\Nymph::exportPrint();
