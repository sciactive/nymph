<?php

require '../lib/require.php';

require '../src/Nymph.php';
RPHP::_('NymphConfig', array(), function(){
	// Nymph's configuration.

	$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');

	$nymph_config->driver['value'] = 'PostgreSQL';
	$nymph_config->PostgreSQL->database['value'] = 'nymph_testing';
	$nymph_config->PostgreSQL->user['value'] = 'nymph_testing';
	$nymph_config->PostgreSQL->password['value'] = 'password';

	return $nymph_config;
});

$Nymph = RPHP::_('Nymph');

require 'TestModel.php';
