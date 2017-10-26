<?php

// Did I really try to do this instead of just setting my own autoloader??

// $files = [
//   __DIR__.'/vendor/composer/autoload_classmap.php',
//   __DIR__.'/vendor/composer/autoload_psr4.php',
//   __DIR__.'/vendor/composer/autoload_namespaces.php',
//   __DIR__.'/vendor/composer/autoload_static.php',
// ];
//
// foreach ($files as $file) {
//   $contents = file_get_contents($file);
//
//   if (strpos($contents, '$vendorDir . \'/sciactive/nymph-server/src')
//       !== false) {
//     $contents = str_replace(
//         [
//           '$vendorDir . \'/sciactive/nymph-pubsub/src',
//           '$vendorDir . \'/sciactive/nymph-server/src',
//         ],
//         [
//           '\''.__DIR__.'/pubsub/src',
//           '\''.__DIR__.'/server/src',
//         ],
//         $contents
//     );
//     file_put_contents($file, $contents);
//   }
//   if (strpos($contents, '__DIR__ . \'/..\' . \'/sciactive/nymph-server/src\'')
//       !== false) {
//     $contents = str_replace(
//         [
//           '__DIR__ . \'/..\' . \'/sciactive/nymph-pubsub/src\'',
//           '__DIR__ . \'/..\' . \'/sciactive/nymph-server/src\'',
//         ],
//         [
//           '\''.__DIR__.'/pubsub/src\'',
//           '\''.__DIR__.'/server/src\'',
//         ],
//         $contents
//     );
//     file_put_contents($file, $contents);
//   }
// }

require __DIR__.'/vendor/autoload.php';

(function () {
  $classMap = [];
  foreach (glob(__DIR__.'/server/src/Drivers/*.php') as $file) {
    $classMap['Nymph\\Drivers\\'.basename($file, '.php')] = $file;
  }
  foreach (glob(__DIR__.'/server/src/Exceptions/*.php') as $file) {
    $classMap['Nymph\\Exceptions\\'.basename($file, '.php')] = $file;
  }
  foreach (glob(__DIR__.'/server/src/*.php') as $file) {
    $classMap['Nymph\\'.basename($file, '.php')] = $file;
  }
  foreach (glob(__DIR__.'/pubsub/src/*.php') as $file) {
    $classMap['Nymph\\PubSub\\'.basename($file, '.php')] = $file;
  }
  spl_autoload_register(function ($className) use ($classMap) {
    if (isset($classMap[$className]))
      include $classMap[$className];
  }, true, true);
})();
