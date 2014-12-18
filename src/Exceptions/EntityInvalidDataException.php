<?php namespace Nymph\Exceptions;
/**
 * EntityInvalidDataException exception.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

class EntityInvalidDataException extends \Exception {
	private $fields = array();

	/**
	 * @param string $message
	 * @param int $code
	 * @param \Exception $previous
	 * @param array|string $fields A field, or an array of fields, which fail validation checking.
	 */
	public function __construct($message = '', $code = 0, $previous = null, $fields = array()) {
		parent::__construct($message, $code, $previous);
		if (!empty($fields)) {
			if ((array) $fields !== $fields) {
				$fields = array((string) $fields);
			}
			$this->fields = $fields;
		}
	}

	public function addField($name) {
		$this->fields[] = $name;
	}

	public function getFields() {
		return $this->fields;
	}
}
