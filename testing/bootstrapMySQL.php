<?php
require_once '../lib/require.php';
use SciActive\R as R;

R::undef('NymphConfig');
R::undef('Nymph');

include '../src/autoload.php';

R::_('NymphConfig', array(), function(){
	// Nymph's configuration.

	$nymph_config = include(__DIR__.DIRECTORY_SEPARATOR.'../conf/defaults.php');
	if (getenv('DATABASE_MYSQL')) {
		$dbopts = parse_url(getenv('DATABASE_MYSQL'));
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

require_once 'TestModel.php';
