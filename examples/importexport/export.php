<?php

if (getenv('DATABASE_URL')) {
	// No import/export on Heroku.
	header('HTTP/1.1 403 Forbidden', true, 403);
	echo "403 Forbidden";
	exit;
}

require '../../lib/require.php';

require '../../src/Nymph.php';
\RPHP::_('NymphConfig', array(), function(){
	return include '../config.php';
});

\RPHP::_(array('Nymph'), function(){
	require '../employee/Employee.php';
	require '../sudoku/Game.php';
	require '../todo/Todo.php';
});

\RPHP::_('Nymph')->exportPrint();
