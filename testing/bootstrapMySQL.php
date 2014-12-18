<?php

require_once '../lib/require.php';

RPHP::undef('NymphConfig');
RPHP::undef('Nymph');

include '../src/Nymph.php';

RPHP::_('NymphConfig', array(), function(){
	// Nymph's configuration.

	$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');
	if (getenv('DATABASE_URL')) {
		$dbopts = parse_url(getenv('DATABASE_URL'));
		$nymph_config->MySQL->database['value'] = ltrim($dbopts["path"],'/');
		$nymph_config->MySQL->host['value'] = $dbopts["host"];
		$nymph_config->MySQL->port['value'] = $dbopts["port"];
		$nymph_config->MySQL->user['value'] = $dbopts["user"];
		$nymph_config->MySQL->password['value'] = key_exists("pass", $dbopts) ? $dbopts["pass"] : '';
	} else {
		$nymph_config->MySQL->host['value'] = '127.0.0.1';
		$nymph_config->MySQL->database['value'] = 'nymph_testing';
		$nymph_config->MySQL->user['value'] = 'nymph_testing';
		$nymph_config->MySQL->password['value'] = 'password';
	}

	return $nymph_config;
});

$Nymph = RPHP::_('Nymph');

require_once 'TestModel.php';
