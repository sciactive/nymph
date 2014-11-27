<?php

class EntityClassTest extends PHPUnit_Framework_TestCase {
	public function testInstantiate() {
		$model = TestModel::factory();


		$this->assertInstanceOf('TestModel', $model);

		$this->assertTrue($model->hasTag('test'));

		$this->assertTrue($model->boolean);

		return $model;
	}
}