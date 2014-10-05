<?php
// Nymph's configuration.

$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');

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

return $nymph_config;