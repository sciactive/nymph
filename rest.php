<?php

require 'lib/require.php';
$require = new RequirePHP();

require 'src/Nymph.php';
$require('NymphConfig', array(), function(){
	return include 'conf/config.php';
});

$NymphREST = $require('NymphREST');

require 'Employee.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	parse_str(file_get_contents("php://input"), $args);
	$NymphREST->run($_SERVER['REQUEST_METHOD'], $args['action'], $args['data']);
} else {
	$NymphREST->run($_SERVER['REQUEST_METHOD'], $_REQUEST['action'], $_REQUEST['data']);
}
