<?php
require_once '../lib/require.php';
use SciActive\R as R;

R::undef('NymphConfig');
R::undef('Nymph');

include '../src/Nymph.php';

R::_('NymphConfig', array(), function(){
	// Nymph's configuration.

	$nymph_config = include(__DIR__.DIRECTORY_SEPARATOR.'../conf/defaults.php');

		$nymph_config->driver['value'] = 'PostgreSQL';
	if (getenv('DATABASE_URL')) {
		$dbopts = parse_url(getenv('DATABASE_URL'));
		$nymph_config->PostgreSQL->database['value'] = ltrim($dbopts["path"],'/');
		$nymph_config->PostgreSQL->host['value'] = $dbopts["host"];
		$nymph_config->PostgreSQL->port['value'] = $dbopts["port"];
		$nymph_config->PostgreSQL->user['value'] = $dbopts["user"];
		$nymph_config->PostgreSQL->password['value'] = key_exists("pass", $dbopts) ? $dbopts["pass"] : '';
	} else {
		$nymph_config->PostgreSQL->database['value'] = 'nymph_testing';
		$nymph_config->PostgreSQL->user['value'] = 'nymph_testing';
		$nymph_config->PostgreSQL->password['value'] = 'password';
	}

	return $nymph_config;
});

$Nymph = R::_('Nymph');

require_once 'TestModel.php';
