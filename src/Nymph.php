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

RPHP::_('Nymph', array('NymphConfig'), function($NymphConfig){
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Interfaces.php');
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Exceptions.php');
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Entity.php');
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'NymphDriver.php');
	$class = 'NymphDriver'.$NymphConfig->driver['value'];
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.$class.'.php');

	$Nymph = new $class($NymphConfig);
	return $Nymph;
});
RPHP::_('NymphREST', array('Nymph'), function($Nymph){
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'NymphREST.php');

	$NymphREST = new NymphREST();
	return $NymphREST;
});
