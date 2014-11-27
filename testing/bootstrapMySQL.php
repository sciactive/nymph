<?php

require '../lib/require.php';

require '../src/Nymph.php';
RPHP::_('NymphConfig', array(), function(){
	// Nymph's configuration.

	$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');

	$nymph_config->MySQL->host['value'] = '127.0.0.1';
	$nymph_config->MySQL->database['value'] = 'nymph_testing';
	$nymph_config->MySQL->user['value'] = 'nymph_testing';
	$nymph_config->MySQL->password['value'] = 'password';

	return $nymph_config;
});

$Nymph = RPHP::_('Nymph');

require 'TestModel.php';
