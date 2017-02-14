<?php

$files = [
  __DIR__.'/vendor/composer/autoload_psr4.php',
  __DIR__.'/vendor/composer/autoload_namespaces.php',
  __DIR__.'/vendor/composer/autoload_static.php',
];

foreach ($files as $file) {
  $contents = file_get_contents($file);

  if (strpos($contents, 'array($vendorDir . \'/sciactive/nymph-server/src\')')
      !== false) {
    $contents = str_replace(
        [
          'array($vendorDir . \'/sciactive/nymph-pubsub/src\')',
          'array($vendorDir . \'/sciactive/nymph-server/src\')',
        ],
        [
          'array(\''.__DIR__.'/pubsub/src\')',
          'array(\''.__DIR__.'/server/src\')',
        ],
        $contents
    );
    file_put_contents($file, $contents);
  }
  if (strpos($contents, '__DIR__ . \'/..\' . \'/sciactive/nymph-server/src\'')
      !== false) {
    $contents = str_replace(
        [
          '__DIR__ . \'/..\' . \'/sciactive/nymph-pubsub/src\'',
          '__DIR__ . \'/..\' . \'/sciactive/nymph-server/src\'',
        ],
        [
          '\''.__DIR__.'/pubsub/src\'',
          '\''.__DIR__.'/server/src\'',
        ],
        $contents
    );
    file_put_contents($file, $contents);
  }
}

require __DIR__.'/vendor/autoload.php';
