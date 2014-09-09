<?php
/**
 * Nymph
 *
 * An object relational mapper with PHP and JavaScript interfaces. Written by
 * Hunter Perrin for 2be.io.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * Setup Nymph in a RequirePHP variable.
 *
 * @param RequirePHP $require The RequirePHP variable.
 */
function setupNymph($require) {
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
}

// Check if the global $require is a RequirePHP instance. If so, setup Nymph.
global $require;
if (is_a($require, 'RequirePHP')) {
	setupNymph($require);
}