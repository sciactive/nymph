<?php
// This file is a demo class that extends the Entity class.

/**
 * @property string $name The todo's text.
 * @property bool $done Whether it's done.
 */
class Todo extends \Nymph\Entity {
	const etype = 'todo';
	protected $clientEnabledMethods = array('archive');
	protected $whitelistData = array('name', 'done');
	protected $protectedTags = array('archived');
	protected $whitelistTags = array();

	public function __construct($id = 0) {
		$this->done = false;
		parent::__construct($id);
	}

	public function info($type) {
		if ($type == 'name' && isset($this->name))
			return $this->name;
		elseif ($type == 'type')
			return 'todo';
		elseif ($type == 'types')
			return 'todos';
		return null;
	}

	public function archive() {
		if ($this->hasTag('archived'))
			return true;
		$this->addTag('archived');
		return $this->save();
	}
}
