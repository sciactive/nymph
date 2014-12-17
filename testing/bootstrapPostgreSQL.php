<?php

require '../lib/require.php';

require '../src/Nymph.php';
RPHP::_('NymphConfig', array(), function(){
	// Nymph's configuration.

	$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');

		$nymph_config->driver['value'] = 'PostgreSQL';
	if (getenv('DATABASE_URL')) {
		$dbopts = parse_url(getenv('DATABASE_URL'));
		$nymph_config->PostgreSQL->database['value'] = ltrim($dbopts["path"],'/');
		$nymph_config->PostgreSQL->host['value'] = $dbopts["host"];
		$nymph_config->PostgreSQL->port['value'] = $dbopts["port"];
		$nymph_config->PostgreSQL->user['value'] = $dbopts["user"];
		$nymph_config->PostgreSQL->password['value'] = $dbopts["pass"];
	} else {
		$nymph_config->PostgreSQL->database['value'] = 'nymph_testing';
		$nymph_config->PostgreSQL->user['value'] = 'nymph_testing';
		$nymph_config->PostgreSQL->password['value'] = 'password';
	}

	return $nymph_config;
});

$Nymph = RPHP::_('Nymph');

require 'TestModel.php';
