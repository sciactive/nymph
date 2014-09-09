<?php
/**
 * Define Nymph exceptions.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

class NymphNotConfiguredException extends Exception {}

class NymphUnableToConnectException extends Exception {}

class NymphQueryFailedException extends Exception {
	protected $query;
	public function __construct($message, $code, $previous, $query = null) {
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}
	final public function getQuery() {
		return $this->query;
	}
}

class NymphInvalidParametersException extends Exception {}