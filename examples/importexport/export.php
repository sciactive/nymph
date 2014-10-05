<?php

require '../../lib/require.php';
$require = new RequirePHP();

require '../../src/Nymph.php';
$require('NymphConfig', array(), function(){
	return include '../config.php';
});

$require(array('Nymph'), function(){
	require '../classes/Employee.php';
	require '../classes/Todo.php';
});

$require('Nymph')->exportPrint();
