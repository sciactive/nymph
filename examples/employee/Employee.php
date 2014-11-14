<?php
// This file is a demo class that extends the Entity class.

define('IS_MANAGER', true);

/**
 * @property string $name The employee's name.
 * @property int $id The employee's ID number.
 * @property string $title The employee's title.
 * @property string $department The employee's department.
 * @property array $subordinates An array of the employee's direct subordinates.
 * @property int $salary The employee's yearly salary, in cents.
 * @property bool $current Whether the employee is currently employed at the company.
 * @property int $start_date The employee's start date.
 * @property int $end_date The employee's end date. (If no longer employed.)
 * @property string $phone The employee's phone number.
 */
class Employee extends Entity {
	const etype = 'employee';

	public function __construct($id = 0) {
		$this->addTag('employee');
		$this->current = true;
		$this->start_date = time();
		$this->subordinates = array();
		parent::__construct($id);
		if (!IS_MANAGER) {
			$this->privateData[] = 'salary';
		}
	}

	public function info($type) {
		if ($type == 'name' && isset($this->name))
			return $this->name;
		elseif ($type == 'type')
			return 'employee';
		elseif ($type == 'types')
			return 'employees';
		return null;
	}

	public function save() {
		// Validate employee data.
		$exc = new EntityInvalidDataException();
		if (empty($this->name)) {
			$exc->addField('name');
		}
		if (empty($this->title)) {
			$exc->addField('title');
		}
		if (empty($this->start_date)) {
			$exc->addField('start_date');
		}
		if ($exc->getFields()) {
			throw $exc;
		}
		// Generate employee ID.
		if (!isset($this->id)) {
			$this->id = RPHP::_('Nymph')->newUID('employee');
		}
		return parent::save();
	}
}
