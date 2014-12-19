<?php namespace Nymph;
/**
 * Nymph
 *
 * An object relational mapper with PHP and JavaScript interfaces. Written by
 * Hunter Perrin for SciActive.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
use SciActive\R as R;

spl_autoload_register(function ($class) {
    $prefix = 'Nymph\\';
    $base_dir = __DIR__.'/';
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader.
        return;
    }
    // Get the relative class name.
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php.
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it.
    if (file_exists($file)) {
        require $file;
    }
});

R::_('Nymph', array('NymphConfig'), function($NymphConfig){
	$class = '\\Nymph\\Drivers\\'.$NymphConfig->driver['value'].'Driver';

	$Nymph = new $class($NymphConfig);
	return $Nymph;
});
R::_('NymphREST', array('Nymph'), function($Nymph){
	$NymphREST = new REST();
	return $NymphREST;
});
