<?php
use Nymph\Nymph as Nymph;

class UIDTest extends PHPUnit_Framework_TestCase {
	public function testDeleteOldTestData() {
		$this->assertTrue(Nymph::deleteUID('TestUID'));
	}

	/**
	 * @depends testDeleteOldTestData
	 */
	public function testNewUID() {
		$this->assertEquals(1, Nymph::newUID('TestUID'));
	}

	/**
	 * @depends testNewUID
	 */
	public function testIncrementUID() {
		$this->assertEquals(2, Nymph::newUID('TestUID'));
	}

	/**
	 * @depends testIncrementUID
	 */
	public function testRetrieveUID() {
		$this->assertEquals(2, Nymph::getUID('TestUID'));
	}

	/**
	 * @depends testRetrieveUID
	 */
	public function testRenameUID() {
		$this->assertTrue(Nymph::renameUID('TestUID', 'NewUID'));
		$this->assertNull(Nymph::getUID('TestUID'));
		$this->assertEquals(2, Nymph::getUID('NewUID'));
		$this->assertTrue(Nymph::renameUID('NewUID', 'TestUID'));
		$this->assertNull(Nymph::getUID('NewUID'));
		$this->assertEquals(2, Nymph::getUID('TestUID'));
	}

	/**
	 * @depends testRenameUID
	 */
	public function testSetUID() {
		$this->assertTrue(Nymph::setUID('TestUID', 5));
		$this->assertEquals(5, Nymph::getUID('TestUID'));
	}

	public function testDeleteUID() {
		$this->assertTrue(Nymph::deleteUID('TestUID'));
		$this->assertNull(Nymph::getUID('TestUID'));
	}
}
