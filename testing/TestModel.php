<?php
// This file is a test class that extends the Entity class.

/**
 * @property string $string A string value
 * @property bool $done Whether it's done.
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
