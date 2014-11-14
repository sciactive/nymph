<?php
// Nymph's configuration.

$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');

// Check for Heroku postgres var.
if (getenv('DATABASE_URL')) {
	$dbopts = parse_url(getenv('DATABASE_URL'));
	$nymph_config->driver['value'] = 'PostgreSQL';
	$nymph_config->driver['use_plperl'] = false;
	$nymph_config->PostgreSQL->database['value'] = ltrim($dbopts["path"],'/');
	$nymph_config->PostgreSQL->host['value'] = $dbopts["host"];
	$nymph_config->PostgreSQL->port['value'] = $dbopts["port"];
	$nymph_config->PostgreSQL->user['value'] = $dbopts["user"];
	$nymph_config->PostgreSQL->password['value'] = $dbopts["pass"];
} else {
	if (true) {
		$nymph_config->MySQL->database['value'] = 'nymph_test';
		$nymph_config->MySQL->user['value'] = 'nymph_test';
		$nymph_config->MySQL->password['value'] = 'omgomg';
	} else {
		$nymph_config->driver['value'] = 'PostgreSQL';
		$nymph_config->driver['use_plperl'] = false;
		$nymph_config->PostgreSQL->database['value'] = 'nymph_test';
		$nymph_config->PostgreSQL->user['value'] = 'nymph_test';
		$nymph_config->PostgreSQL->password['value'] = 'omgomg';
	}
}

return $nymph_config;