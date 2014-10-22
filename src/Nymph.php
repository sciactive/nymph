<?php
/**
 * Nymph
 *
 * An object relational mapper with PHP and JavaScript interfaces. Written by
 * Hunter Perrin for 2be.io.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

define('NYMPH_VERSION', '0.0.3alpha');

/**
 * Setup Nymph in a RequirePHP variable.
 *
 * @param RequirePHP $require The RequirePHP variable.
 */
function setupNymph($require) {
	$GLOBALS['NymphRequire'] = &$require;
	$require('Nymph', array('NymphConfig'), function($NymphConfig){
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.'Interfaces.php');
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.'Exceptions.php');
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.'Entity.php');
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.'NymphDriver.php');
		$class = 'NymphDriver'.$NymphConfig->driver['value'];
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.$class.'.php');

		$Nymph = new $class($NymphConfig);
		return $Nymph;
	});
	$require('NymphREST', array('Nymph'), function(){
		require(dirname(__FILE__).DIRECTORY_SEPARATOR.'NymphREST.php');

		$NymphREST = new NymphREST();
		return $NymphREST;
	});
}

// Check if the global $require is a RequirePHP instance. If so, setup Nymph.
global $require;
if (is_a($require, 'RequirePHP')) {
	setupNymph($require);
}