<?php
// This file is a test class that extends the Entity class.

/**
 * @property string $name A string.
 * @property null $null A null.
 * @property string $string A string.
 * @property string $test A string.
 * @property array $array An string.
 * @property string $match A string.
 * @property integer $number A number.
 * @property bool $boolean A boolean.
 * @property TestModel $reference A TestModel.
 * @property array $ref_array An array.
 * @property stdClass $ref_object An object.
 * @property TestModel $parent A parent entity.
 */
class TestModel extends \Nymph\Entity {
	const etype = 'test_model';
	protected $privateData = array('boolean');
	protected $whitelistData = array('string', 'array', 'mdate');
	protected $protectedTags = array('test', 'notag');
	protected $whitelistTags = array('newtag');

	public function __construct($id = 0) {
		$this->addTag('test');
		$this->boolean = true;
		parent::__construct($id);
	}

	public function info($type) {
		if ($type == 'name' && isset($this->name)) {
			return $this->name;
		} elseif ($type == 'type') {
			return 'test';
		} elseif ($type == 'types') {
			return 'tests';
		}
		return null;
	}

	public function useProtectedData() {
		$this->whitelistData = false;
		$this->protectedData = array('number');
	}
}
