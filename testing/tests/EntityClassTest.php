<?php

class EntityClassTest extends PHPUnit_Framework_TestCase {
	public function testInstantiate() {
		$testEntity = TestModel::factory();


		$this->assertInstanceOf('TestModel', $testEntity);

		$this->assertTrue($testEntity->hasTag('test'));

		$this->assertTrue($testEntity->boolean);

		return $testEntity;
	}

	/**
	 * @depends testInstantiate
	 */
	public function testAssignment($testEntity) {
		// Assign some variables.
		$testEntity->name = 'Entity Test';
		$testEntity->null = null;
		$testEntity->string = 'test';
		$testEntity->array = array('full', 'of', 'values', 500);
		$testEntity->number = 30;

		$this->assertSame('Entity Test', $testEntity->name);
		$this->assertNull($testEntity->null);
		$this->assertSame('test', $testEntity->string);
		$this->assertSame(array('full', 'of', 'values', 500), $testEntity->array);
		$this->assertSame(30, $testEntity->number);

		$this->assertTrue($testEntity->save());

		$entity_reference_test = new TestModel();
		$entity_reference_test->string = 'wrong';
		$this->assertTrue($entity_reference_test->save());
		$entity_reference_guid = $entity_reference_test->guid;
		$testEntity->reference = $entity_reference_test;
		$testEntity->ref_array = array(0 => array('entity' => $entity_reference_test));
		$this->assertTrue($testEntity->save());

		$entity_reference_test->test = 'good';
		$this->assertTrue($entity_reference_test->save());

		return array('entity' => $testEntity, 'refGuid' => $entity_reference_guid);
	}

	/**
	 * @depends testAssignment
	 */
	public function testComparison($arr) {
		$testEntity = $arr['entity'];
		$compare = TestModel::factory($testEntity->guid);

		$this->assertTrue($testEntity->is($compare));
		$testEntity->refresh();
		$compare->refresh();
		$this->assertTrue($testEntity->equals($compare));

		$compare->string = 'different';

		$this->assertTrue($testEntity->is($compare));
		$this->assertFalse($testEntity->equals($compare));
	}

	/**
	 * @depends testAssignment
	 */
	public function testArraySearching($arr) {
		$testEntity = $arr['entity'];
		$array = array('thing', TestModel::factory($testEntity->guid));

		$this->assertTrue($testEntity->inArray($array));
		$testEntity->refresh();
		$array[1]->refresh();
		$this->assertTrue($testEntity->inArray($array, true));
		$this->assertFalse($testEntity->inArray(array(0, 1, 2, 3, 4, 5)));
		$this->assertFalse($testEntity->inArray(array(0, 1, 2, 3, 4, 5), true));

		$array[1]->string = 'different';

		$this->assertTrue($testEntity->inArray($array));
		$this->assertFalse($testEntity->inArray($array, true));

		$this->assertSame(1, $testEntity->arraySearch($array));
		$testEntity->refresh();
		$array[1]->refresh();
		$this->assertSame(1, $testEntity->arraySearch($array, true));
		$this->assertSame(false, $testEntity->arraySearch(array(0, 1, 2, 3, 4, 5)));
		$this->assertSame(false, $testEntity->arraySearch(array(0, 1, 2, 3, 4, 5), true));

		$array[1]->string = 'different';

		$this->assertSame(1, $testEntity->arraySearch($array));
		$this->assertSame(false, $testEntity->arraySearch($array, true));
	}

	/**
	 * @depends testAssignment
	 */
	public function testRefresh($arr) {
		$testEntity = $arr['entity'];

		$testEntity->null = true;
		$this->assertTrue($testEntity->null);
		$this->assertTrue($testEntity->refresh());
		$this->assertNull($testEntity->null);
	}

	/**
	 * @depends testAssignment
	 */
	public function testUpdateRefresh($arr) {
		$testEntity = $arr['entity'];

		$this->assertSame('test', $testEntity->string);
		$testEntity->string = 'updated';
		$this->assertTrue($testEntity->save());
		$testEntity->refresh();
		$this->assertTrue($testEntity->save());

		$retrieve = TestModel::factory($testEntity->guid);
		$this->assertSame('updated', $retrieve->string);
		$testEntity->string = 'test';
		$this->assertTrue($testEntity->save());

		$testEntity->refresh();
		$this->assertSame('test', $testEntity->string);
	}

	/**
	 * @depends testAssignment
	 */
	public function testToReference($arr) {
		$testEntity = $arr['entity'];

		$reference = $testEntity->toReference();

		$this->assertEquals(array('nymph_entity_reference', $testEntity->guid, 'TestModel'), $reference);
	}

	/**
	 * @depends testAssignment
	 */
	public function testTags($arr) {
		$testEntity = $arr['entity'];

		$this->assertTrue($testEntity->hasTag('test'));
		$testEntity->addTag('test', 'test2');
		$this->assertTrue($testEntity->hasTag('test', 'test2'));
		$testEntity->addTag(['test', 'test3', 'test4', 'test5', 'test6']);
		$this->assertTrue($testEntity->hasTag(array('test', 'test3', 'test4', 'test5', 'test6')));
		$testEntity->removeTag('test2');
		$this->assertFalse($testEntity->hasTag('test2'));
		$testEntity->removeTag('test3', 'test4');
		$this->assertFalse($testEntity->hasTag('test3', 'test4'));
		$testEntity->removeTag(['test5', 'test6']);
		$this->assertFalse($testEntity->hasTag(array('test5', 'test6')));
		$this->assertEquals(array('test'), $testEntity->getTags());
	}

	/**
	 * @depends testAssignment
	 */
	public function testReferences($arr) {
		$testEntity = $arr['entity'];

		$testEntity->refresh();

		$this->assertSame($arr['refGuid'], $testEntity->reference->guid);
		$this->assertSame($arr['refGuid'], $testEntity->ref_array[0]['entity']->guid);

		$entity = TestModel::factory($testEntity->guid);

		$this->assertSame($arr['refGuid'], $entity->reference->guid);
		$this->assertSame($arr['refGuid'], $entity->ref_array[0]['entity']->guid);
	}

	/**
	 * @depends testAssignment
	 */
	public function testSleepingReferences($arr) {
		$testEntity = $arr['entity'];

		$entity = TestModel::factoryReference(array('nymph_sleeping_reference', $testEntity->guid, 'TestModel'));

		$this->assertSame('Entity Test', $entity->name);
		$this->assertNull($entity->null);
		$this->assertSame('test', $entity->string);
		$this->assertSame(array('full', 'of', 'values', 500), $entity->array);
		$this->assertSame(30, $entity->number);
		$this->assertSame($arr['refGuid'], $entity->reference->guid);
		$this->assertSame($arr['refGuid'], $entity->ref_array[0]['entity']->guid);
	}

	/**
	 * @depends testAssignment
	 */
	public function testJSON($arr) {
		$testEntity = $arr['entity'];

		$json = json_encode($testEntity);

		$this->assertJsonStringEqualsJsonString(
			'{"guid":'.$testEntity->guid.',"cdate":'.json_encode($testEntity->cdate).',"mdate":'.json_encode($testEntity->mdate).',"tags":["test"],"info":{"name":"Entity Test","type":"test","types":"tests"},"data":{"reference":["nymph_entity_reference",'.$arr['refGuid'].',"TestModel"],"ref_array":[{"entity":["nymph_entity_reference",'.$arr['refGuid'].',"TestModel"]}],"name":"Entity Test","number":30,"array":["full","of","values",500],"string":"test","null":null},"class":"TestModel"}',
			$json
		);
	}

	/**
	 * @depends testAssignment
	 */
	public function testAcceptJSON($arr) {
		$testEntity = $arr['entity'];

		$json = json_encode($testEntity);

		$entityData = json_decode($json, true);

		$entityData['tags'] = array('test', 'notag', 'newtag');
		$testEntity->jsonAcceptTags($entityData['tags']);
		$entityData['data']['cdate'] = 13;
		$entityData['data']['mdate'] = 14;
		$entityData['data']['name'] = 'bad';
		$entityData['data']['string'] = 'good';
		$entityData['data']['null'] = true;
		$entityData['data']['array'] = array('imanarray');
		$entityData['data']['number'] = 4;
		$entityData['data']['reference'] = false;
		$entityData['data']['ref_array'] = array(false);
		$testEntity->jsonAcceptData($entityData['data']);

		$this->assertFalse($testEntity->hasTag('notag'));
		$this->assertTrue($testEntity->hasTag('newtag'));
		$this->assertGreaterThan(13, $testEntity->cdate);
		$this->assertSame(14, $testEntity->mdate);
		$this->assertSame('Entity Test', $testEntity->name);
		$this->assertNull($testEntity->null);
		$this->assertSame('good', $testEntity->string);
		$this->assertSame(array('imanarray'), $testEntity->array);
		$this->assertSame(30, $testEntity->number);
		$this->assertSame($arr['refGuid'], $testEntity->reference->guid);
		$this->assertSame($arr['refGuid'], $testEntity->ref_array[0]['entity']->guid);

		$this->assertTrue($testEntity->refresh());
		$testEntity->useProtectedData();

		$testEntity->jsonAcceptTags($entityData['tags']);
		$testEntity->jsonAcceptData($entityData['data']);

		$this->assertFalse($testEntity->hasTag('notag'));
		$this->assertTrue($testEntity->hasTag('newtag'));
		$this->assertSame(13, $testEntity->cdate);
		$this->assertSame(14, $testEntity->mdate);
		$this->assertSame('bad', $testEntity->name);
		$this->assertTrue($testEntity->null);
		$this->assertSame('good', $testEntity->string);
		$this->assertSame(array('imanarray'), $testEntity->array);
		$this->assertSame(30, $testEntity->number);
		$this->assertFalse($testEntity->reference);
		$this->assertSame(array(false), $testEntity->ref_array);

		$this->assertTrue($testEntity->refresh());
	}

	public function testSort() {
		$first = new TestModel();
		$first->name = 'Thing A';
		$second = new TestModel();
		$second->name = 'thing B';
		$third = new TestModel();
		$third->name = 'Thing C';
		$fourth = new TestModel();
		$fourth->name = 'Thing D';
		$fifth = new TestModel();
		$fifth->name = 'Thing E';
		$sixth = new TestModel();
		$sixth->name = 'Thing F';
		$seventh = new TestModel();
		$seventh->name = 'Thing G';
		$eighth = new TestModel();
		$eighth->name = 'Thing H';
		$ninth = new TestModel();
		$ninth->name = 'Thing I';
		$tenth = new TestModel();
		$tenth->name = 'Thing J';

		$arr = array(
			$second,
			$sixth,
			$ninth,
			$fifth,
			$third,
			$tenth,
			$fourth,
			$first,
			$seventh,
			$eighth
		);

		\SciActive\R::_('Nymph')->sort($arr, 'name');

		$this->assertEquals(array(
			$first,
			$second,
			$third,
			$fourth,
			$fifth,
			$sixth,
			$seventh,
			$eighth,
			$ninth,
			$tenth
		), $arr);

		\SciActive\R::_('Nymph')->sort($arr, 'name', true, true);

		$this->assertEquals(array_reverse(array(
			$first,
			$third,
			$fourth,
			$fifth,
			$sixth,
			$seventh,
			$eighth,
			$ninth,
			$tenth,
			$second
		)), $arr);
	}

	public function testHSort() {
		$first = new TestModel();
		$first->name = 'Thing A';
		$firstsub1 = new TestModel();
		$firstsub1->name = 'Thing 0';
		$firstsub1->parent = $first;
		$firstsub2 = new TestModel();
		$firstsub2->name = 'Thing 1';
		$firstsub2->parent = $first;
		$second = new TestModel();
		$second->name = 'thing B';
		$third = new TestModel();
		$third->name = 'Thing C';
		$firstsub3 = new TestModel();
		$firstsub3->name = 'Thing 0';
		$firstsub3->parent = $third;
		$fourth = new TestModel();
		$fourth->name = 'Thing D';
		$firstsub4 = new TestModel();
		$firstsub4->name = 'Thing 0';
		$firstsub4->parent = $fourth;
		$firstsub5 = new TestModel();
		$firstsub5->name = 'Thing 0.0';
		$firstsub5->parent = $firstsub4;
		$firstsub6 = new TestModel();
		$firstsub6->name = 'Thing 1';
		$firstsub6->parent = $fourth;
		$fifth = new TestModel();
		$fifth->name = 'Thing E';
		$sixth = new TestModel();
		$sixth->name = 'Thing F';
		$seventh = new TestModel();
		$seventh->name = 'Thing G';
		$eighth = new TestModel();
		$eighth->name = 'Thing H';
		$ninth = new TestModel();
		$ninth->name = 'Thing I';
		$tenth = new TestModel();
		$tenth->name = 'Thing J';

		$arr = array(
			$second,
			$sixth,
			$firstsub6,
			$ninth,
			$firstsub2,
			$fifth,
			$third,
			$firstsub3,
			$tenth,
			$fourth,
			$first,
			$firstsub5,
			$seventh,
			$eighth,
			$firstsub4,
			$firstsub1
		);

		\SciActive\R::_('Nymph')->hsort($arr, 'name', 'parent');

		/*foreach ($arr as $cur) {
			echo "\n".(isset($cur->parent) ? (isset($cur->parent->parent) ? "{$cur->parent->parent->name} : " : '')."{$cur->parent->name} : " : '')."$cur->name\n";
		}*/

		$this->assertEquals(array(
			$first,
			$firstsub1,
			$firstsub2,
			$second,
			$third,
			$firstsub3,
			$fourth,
			$firstsub4,
			$firstsub5,
			$firstsub6,
			$fifth,
			$sixth,
			$seventh,
			$eighth,
			$ninth,
			$tenth
		), $arr);

		\SciActive\R::_('Nymph')->hsort($arr, 'name', 'parent', true, true);

		/*foreach ($arr as $cur) {
			echo "\n".(isset($cur->parent) ? (isset($cur->parent->parent) ? "{$cur->parent->parent->name} : " : '')."{$cur->parent->name} : " : '')."$cur->name\n";
		}*/

		$this->assertEquals(array(
			$second,
			$tenth,
			$ninth,
			$eighth,
			$seventh,
			$sixth,
			$fifth,
			$fourth,
			$firstsub6,
			$firstsub4,
			$firstsub5,
			$third,
			$firstsub3,
			$first,
			$firstsub2,
			$firstsub1,
		), $arr);
	}
}
