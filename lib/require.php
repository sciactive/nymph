<?php
/**
 * RequirePHP
 *
 * An implementation of dependency injection and service location (like
 * RequireJS) in PHP. Written by Hunter Perrin for 2be.io.
 *
 * @version 1.0.0beta1
 * @license https://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://requirephp.org
 */

define('REQUIREPHP_MAX_DEPTH', 80);

class RequirePHP {
	private $modules = array();
	private $functions = array();
	private $aliases = array();
	private $depth = 0;

	public function &__invoke($arg1 = null, $arg2 = null, $arg3 = null) {
		if (isset($arg1) && !isset($arg2) && !isset($arg3)) { // Calling require('name') to get the object.
			$arg1 = $this->parseAlias($arg1);
			if (!$this->runModule($arg1))
				throw new RequireModuleFailedException("Can't load module $arg1.");
			return $this->modules[$arg1]['return'];
		} elseif (!isset($arg3) && is_array($arg1) && is_callable($arg2)) { // Calling require(['dependency'], function(){}) to run a function when dependencies are met.
			$this->functions[] = array('requires' => $arg1, 'function' => $arg2);
		} elseif (is_string($arg1) && is_array($arg2) && is_callable($arg3)) { // Calling require('name', ['dependency'], function(){}) to declare a named module.
			$this->modules[$arg1] = array('requires' => $arg2, 'function' => $arg3);
		}
		$this->runFunctions();
		return $this;
	}

	public function call() {
		return call_user_func_array($this, func_get_args());
	}

	public function alias($name, $target) {
		$name = "$name";
		$target = "$target";
		if (empty($name) || empty($target))
			return false;
		$this->aliases[$name] = $target;
		return $this;
	}

	private function parseAlias($name) {
		if (isset($this->modules[$name]))
			return $name;
		if (isset($this->aliases[$name]))
			return $this->parseAlias($this->aliases[$name]);
		return $name;
	}

	private function runModule($name) {
		$name = $this->parseAlias($name);
		if (!isset($this->modules[$name]))
			return false;
		if (key_exists('return', $this->modules[$name])) // If we've already loaded this module, we're golden.
			return true;
		$this->depth++; // Keep track of how deep we're going.
		if ($this->depth > REQUIREPHP_MAX_DEPTH) {
			$this->depth--;
			throw new RequireTooDeepException("Proceeded too deeply down the rabbit hole. Max require depth is ".REQUIREPHP_MAX_DEPTH.".");
		}
		// Load the required modules.
		$arguments = array();
		if (!empty($this->modules[$name]['requires'])) {
			foreach ($this->modules[$name]['requires'] as $require) {
				$require = $this->parseAlias($require);
				if (!$this->runModule($require)) {
					$this->depth--;
					return false;
				}
				$arguments[] = $this->modules[$require]['return']; // Add this return value to the arguments. We'll pass it to the callback.
			}
		}
		if (is_callable($this->modules[$name]['function'])) {
			$this->modules[$name]['return'] = call_user_func_array($this->modules[$name]['function'], $arguments);
		} else {
			$this->depth--;
			return false;
		}
		$this->depth--;
		return true;
	}

	private function runFunctions() {
		foreach ($this->functions as $key => $function) {
			// Load the required modules.
			$arguments = array();
			foreach ($function['requires'] as $require) {
				$require = $this->parseAlias($require);
				if (!$this->runModule($require))
					continue 2;
				$arguments[] = $this->modules[$require]['return']; // Add this return value to the arguments. We'll pass it to the callback.
			}
			call_user_func_array($function['function'], $arguments);
			unset($this->functions[$key]);
		}
	}
}

class RequireTooDeepException extends Exception {}
class RequireModuleFailedException extends Exception {}
