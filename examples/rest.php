<?php

require '../lib/require.php';

require '../src/Nymph.php';
RPHP::_('NymphConfig', array(), function(){
	return include 'config.php';
});

$NymphREST = RPHP::_('NymphREST');

require 'classes/Employee.php';
require 'classes/Todo.php';

if (in_array($_SERVER['REQUEST_METHOD'], array('PUT', 'DELETE'))) {
	parse_str(file_get_contents("php://input"), $args);
	$NymphREST->run($_SERVER['REQUEST_METHOD'], $args['action'], $args['data']);
} else {
	$NymphREST->run($_SERVER['REQUEST_METHOD'], $_REQUEST['action'], $_REQUEST['data']);
}
