<?php

class EntityTest extends PHPUnit_Framework_TestCase {
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
		$testEntity->array = ['full', 'of', 'values', 500];
		$testEntity->number = 30;

		$this->assertSame('Entity Test', $testEntity->name);
		$this->assertNull($testEntity->null);
		$this->assertSame('test', $testEntity->string);
		$this->assertSame(['full', 'of', 'values', 500], $testEntity->array);
		$this->assertSame(30, $testEntity->number);

		$this->assertTrue($testEntity->save());

		$entity_reference_test = new TestModel();
		$entity_reference_test->string = 'wrong';
		$this->assertTrue($entity_reference_test->save());
		$entity_reference_guid = $entity_reference_test->guid;
		$testEntity->reference = $entity_reference_test;
		$testEntity->ref_array = [0 => ['entity' => $entity_reference_test]];
		$testEntity->ref_object = (object) ['thing' => (object) ['entity' => $entity_reference_test]];
		$this->assertTrue($testEntity->save());

		$entity_reference_test->test = 'good';
		$this->assertTrue($entity_reference_test->save());

		return ['entity' => $testEntity, 'refGuid' => $entity_reference_guid];
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
		$array = ['thing', TestModel::factory($testEntity->guid)];

		$this->assertTrue($testEntity->inArray($array));
		$testEntity->refresh();
		$array[1]->refresh();
		$this->assertTrue($testEntity->inArray($array, true));
		$this->assertFalse($testEntity->inArray([0, 1, 2, 3, 4, 5]));
		$this->assertFalse($testEntity->inArray([0, 1, 2, 3, 4, 5], true));

		$array[1]->string = 'different';

		$this->assertTrue($testEntity->inArray($array));
		$this->assertFalse($testEntity->inArray($array, true));

		$this->assertSame(1, $testEntity->arraySearch($array));
		$testEntity->refresh();
		$array[1]->refresh();
		$this->assertSame(1, $testEntity->arraySearch($array, true));
		$this->assertSame(false, $testEntity->arraySearch([0, 1, 2, 3, 4, 5]));
		$this->assertSame(false, $testEntity->arraySearch([0, 1, 2, 3, 4, 5], true));

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

		$this->assertEquals(['nymph_entity_reference', $testEntity->guid, 'TestModel'], $reference);
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
		$this->assertTrue($testEntity->hasTag(['test', 'test3', 'test4', 'test5', 'test6']));
		$testEntity->removeTag('test2');
		$this->assertFalse($testEntity->hasTag('test2'));
		$testEntity->removeTag('test3', 'test4');
		$this->assertFalse($testEntity->hasTag('test3', 'test4'));
		$testEntity->removeTag(['test5', 'test6']);
		$this->assertFalse($testEntity->hasTag(['test5', 'test6']));
		$this->assertEquals(['test'], $testEntity->getTags());
	}

	/**
	 * @depends testAssignment
	 */
	public function testReferences($arr) {
		$testEntity = $arr['entity'];

		$testEntity->refresh();

		$this->assertSame($arr['refGuid'], $testEntity->reference->guid);
		$this->assertSame($arr['refGuid'], $testEntity->ref_array[0]['entity']->guid);
		$this->assertSame($arr['refGuid'], $testEntity->ref_object->thing->entity->guid);

		$entity = TestModel::factory($testEntity->guid);

		$this->assertSame($arr['refGuid'], $entity->reference->guid);
		$this->assertSame($arr['refGuid'], $entity->ref_array[0]['entity']->guid);
		$this->assertSame($arr['refGuid'], $entity->ref_object->thing->entity->guid);
	}

	/**
	 * @depends testAssignment
	 */
	public function testSleepingReferences($arr) {
		$testEntity = $arr['entity'];

		$entity = TestModel::factoryReference(['nymph_sleeping_reference', $testEntity->guid, 'TestModel']);

		$this->assertSame('Entity Test', $entity->name);
		$this->assertNull($entity->null);
		$this->assertSame('test', $entity->string);
		$this->assertSame(['full', 'of', 'values', 500], $entity->array);
		$this->assertSame(30, $entity->number);
		$this->assertSame($arr['refGuid'], $entity->reference->guid);
		$this->assertSame($arr['refGuid'], $entity->ref_array[0]['entity']->guid);
		$this->assertSame($arr['refGuid'], $entity->ref_object->thing->entity->guid);
	}

	/**
	 * @depends testAssignment
	 */
	public function testJSON($arr) {
		$testEntity = $arr['entity'];

		$json = json_encode($testEntity);

		$this->assertJsonStringEqualsJsonString(
			'{"guid":'.$testEntity->guid.',"cdate":'.json_encode($testEntity->cdate).',"mdate":'.json_encode($testEntity->mdate).',"tags":["test"],"info":{"name":"Entity Test","type":"test","types":"tests"},"data":{"reference":["nymph_entity_reference",'.$arr['refGuid'].',"TestModel"],"ref_array":[{"entity":["nymph_entity_reference",'.$arr['refGuid'].',"TestModel"]}],"ref_object":{"thing":{"entity":["nymph_entity_reference",'.$arr['refGuid'].',"TestModel"]}},"name":"Entity Test","number":30,"array":["full","of","values",500],"string":"test","null":null},"class":"TestModel"}',
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

		$entityData['tags'] = ['test', 'notag', 'newtag'];
		$testEntity->jsonAcceptTags($entityData['tags']);
		$entityData['data']['cdate'] = 13;
		$entityData['data']['mdate'] = 14;
		$entityData['data']['name'] = 'bad';
		$entityData['data']['string'] = 'good';
		$entityData['data']['null'] = true;
		$entityData['data']['array'] = ['imanarray'];
		$entityData['data']['number'] = 4;
		$entityData['data']['reference'] = false;
		$entityData['data']['ref_array'] = [false];
		$entityData['data']['ref_object'] = (object) ["thing"=>false];
		$testEntity->jsonAcceptData($entityData['data']);

		$this->assertFalse($testEntity->hasTag('notag'));
		$this->assertTrue($testEntity->hasTag('newtag'));
		$this->assertGreaterThan(13, $testEntity->cdate);
		$this->assertSame(14, $testEntity->mdate);
		$this->assertSame('Entity Test', $testEntity->name);
		$this->assertNull($testEntity->null);
		$this->assertSame('good', $testEntity->string);
		$this->assertSame(['imanarray'], $testEntity->array);
		$this->assertSame(30, $testEntity->number);
		$this->assertSame($arr['refGuid'], $testEntity->reference->guid);
		$this->assertSame($arr['refGuid'], $testEntity->ref_array[0]['entity']->guid);
		$this->assertSame($arr['refGuid'], $testEntity->ref_object->thing->entity->guid);

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
		$this->assertSame(['imanarray'], $testEntity->array);
		$this->assertSame(30, $testEntity->number);
		$this->assertFalse($testEntity->reference);
		$this->assertSame([false], $testEntity->ref_array);
		$this->assertEquals((object) ["thing"=>false], $testEntity->ref_object);

		$this->assertTrue($testEntity->refresh());
	}
}
