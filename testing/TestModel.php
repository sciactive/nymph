<?php
// This file is a test class that extends the Entity class.

/**
 * @property string $name A string.
 * @property null $null A null.
 * @property string $string A string.
 * @property array $array An string.
 * @property string $match A string.
 * @property integer $number A number.
 * @property bool $boolean A boolean.
 * @property TestModel $reference A TestModel.
 * @property array $ref_array An array.
 */
class TestModel extends Entity {
	const etype = 'test_model';
	protected $whitelistData = array('string', 'boolean');
	protected $protectedTags = array('test');
	protected $whitelistTags = array();

	public function __construct($id = 0) {
		$this->addTag('test');
		$this->boolean = true;
		parent::__construct($id);
	}

	public function info($type) {
		if ($type == 'name' && isset($this->name))
			return $this->name;
		elseif ($type == 'type')
			return 'test';
		elseif ($type == 'types')
			return 'tests';
		return null;
	}
}
