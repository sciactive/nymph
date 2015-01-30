<?php namespace Nymph\Exceptions;
/**
 * QueryFailedException exception.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

class QueryFailedException extends \Exception {
	protected $query;
	public function __construct($message, $code, $previous, $query = null) {
		if ($query) {
			$message .= "\nFull query: ".$query;
		}
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}
	final public function getQuery() {
		return $this->query;
	}
}
