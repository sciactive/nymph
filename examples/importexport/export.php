<?php

require '../../lib/require.php';

require '../../src/Nymph.php';
RPHP::_('NymphConfig', array(), function(){
	return include '../config.php';
});

RPHP::_(array('Nymph'), function(){
	require '../classes/Employee.php';
	require '../classes/Todo.php';
});

RPHP::_('Nymph')->exportPrint();
