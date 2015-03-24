<?php

file_put_contents(__DIR__.'/vendor/composer/autoload_psr4.php', str_replace([
	'array($vendorDir . \'/sciactive/nymph-pubsub/src\')',
	'array($vendorDir . \'/sciactive/nymph-server/src\')',
], [
	'array(\''.__DIR__.'/pubsub/src\')',
	'array(\''.__DIR__.'/server/src\')',
], file_get_contents(__DIR__.'/vendor/composer/autoload_psr4.php')));

require __DIR__.'/vendor/autoload.php';
