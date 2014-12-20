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

R::_('Nymph', array('NymphConfig'), function($NymphConfig){
	$class = '\\Nymph\\Drivers\\'.$NymphConfig->driver['value'].'Driver';

	$Nymph = new $class($NymphConfig);
	return $Nymph;
});

class Nymph {
	public static function __callStatic($name, $args) {
		return call_user_func_array(array(R::_('Nymph'), $name), $args);
	}

	// Any method with an argument passed by reference must be redeclared.
	public static function deleteEntity(&$entity) {
		return R::_('Nymph')->deleteEntity($entity);
	}

	public static function saveEntity(&$entity) {
		return R::_('Nymph')->saveEntity($entity);
	}

	public static function hsort(&$array, $property = null, $parent_property = null, $case_sensitive = false, $reverse = false) {
		return R::_('Nymph')->hsort($array, $property, $parent_property, $case_sensitive, $reverse);
	}

	public static function psort(&$array, $property = null, $parent_property = null, $case_sensitive = false, $reverse = false) {
		return R::_('Nymph')->psort($array, $property, $parent_property, $case_sensitive, $reverse);
	}

	public static function sort(&$array, $property = null, $case_sensitive = false, $reverse = false) {
		return R::_('Nymph')->sort($array, $property, $case_sensitive, $reverse);
	}
}
