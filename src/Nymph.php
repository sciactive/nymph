<?php
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

define('NYMPH_VERSION', '1.0.0beta3');

RPHP::_('Nymph', array('NymphConfig'), function($NymphConfig){
	require(dirname(__FILE__).DIRECTORY_SEPARATOR.'Interfaces.php');
	require(dirname(__FILE__).DIRECTORY_SEPARATOR.'Exceptions.php');
	require(dirname(__FILE__).DIRECTORY_SEPARATOR.'Entity.php');
	require(dirname(__FILE__).DIRECTORY_SEPARATOR.'NymphDriver.php');
	$class = 'NymphDriver'.$NymphConfig->driver['value'];
	require(dirname(__FILE__).DIRECTORY_SEPARATOR.$class.'.php');

	$Nymph = new $class($NymphConfig);
	return $Nymph;
});
RPHP::_('NymphREST', array('Nymph'), function($Nymph){
	require(dirname(__FILE__).DIRECTORY_SEPARATOR.'NymphREST.php');

	$NymphREST = new NymphREST();
	return $NymphREST;
});
