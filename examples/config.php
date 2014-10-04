<?php
// Nymph's configuration.

$nymph_config = include(dirname(__FILE__).DIRECTORY_SEPARATOR.'../conf/defaults.php');

$nymph_config->MySQL->database['value'] = 'nymph_test';
$nymph_config->MySQL->user['value'] = 'nymph_test';
$nymph_config->MySQL->password['value'] = 'omgomg';

return $nymph_config;