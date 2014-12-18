<?php

class QueriesTest extends PHPUnit_Framework_TestCase {
	public function testDeleteOldTestData() {
		$nymph = \RPHP::_('Nymph');
		$this->assertInstanceOf('\\Nymph\\Drivers\\DriverInterface', $nymph);

		$all = $nymph->getEntities(array('class' => 'TestModel'));
		$this->assertTrue((array)$all===$all);
		foreach ($all as $cur) {
			$this->assertTrue($cur->delete());
		}

		$all = $nymph->getEntities(array('class' => 'TestModel'));
		$this->assertEmpty($all);

		return $nymph;
	}

	/**
	 * @depends testDeleteOldTestData
	 */
	public function testCreateEntity($nymph) {
		// Creating entity...
		$testEntity = TestModel::factory();
		$this->assertInstanceOf('TestModel', $testEntity);

		// Saving entity...
		$testEntity->name = 'Entity Test '.time();
		$testEntity->null = null;
		$testEntity->string = 'test';
		$testEntity->array = array('full', 'of', 'values', 500);
		$testEntity->match = "Hello, my name is Edward McCheese. It is a pleasure to meet you. As you can see, I have several hats of the most pleasant nature.

This one's email address is nice_hat-wednesday+newyork@im-a-hat.hat.
This one's phone number is (555) 555-1818.
This one's zip code is 92064.";
		$testEntity->number = 30;
		$this->assertTrue($testEntity->save());
		$entity_guid = $testEntity->guid;

		$entity_reference_test = new TestModel();
		$entity_reference_test->string = 'wrong';
		$this->assertTrue($entity_reference_test->save());
		$entity_reference_guid = $entity_reference_test->guid;
		$testEntity->reference = $entity_reference_test;
		$testEntity->ref_array = array(0 => array('entity' => $entity_reference_test));
		$this->assertTrue($testEntity->save());

		$entity_reference_test->test = 'good';
		$this->assertTrue($entity_reference_test->save());

		$testEntity = $nymph->getEntity(array('class' => 'TestModel'), $entity_guid);
		$this->assertInstanceOf('TestModel', $testEntity);

		return array('nymph' => $nymph, 'entity' => $testEntity, 'refGuid' => $entity_reference_guid);
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testByGuid($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID...
		$resultEntity = $nymph->getEntity(array('class' => 'TestModel'), $testEntity->guid);
		$this->assertTrue($testEntity->is($resultEntity));

		// Using class constructor...
		$resultEntity = TestModel::factory($testEntity->guid);
		$this->assertTrue($testEntity->is($resultEntity));

		// Testing wrong GUID...
		$resultEntity = $nymph->getEntity(array('class' => 'TestModel'), $testEntity->guid + 1);
		if (!empty($resultEntity)) {
			$this->assertTrue(!$testEntity->is($resultEntity));
		} else {
			$this->assertNull($resultEntity);
		}
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testOptions($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing entity order, offset, limit...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel', 'reverse' => true, 'offset' => 1, 'limit' => 1, 'sort' => 'cdate'),
				array('&', 'tag' => 'test')
			);
		$this->assertTrue($testEntity->is($resultEntity[0]));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testGUIDAndTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID and tags...
		$resultEntity = $nymph->getEntity(
				array('class' => 'TestModel'),
				array('&', 'guid' => $testEntity->guid, 'tag' => 'test')
			);
		$this->assertTrue($testEntity->is($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testOrSelector($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID and tags...
		$resultEntity = $nymph->getEntity(
				array('class' => 'TestModel'),
				array('|', 'guid' => array($testEntity->guid, $testEntity->guid % 1000 + 1))
			);
		$this->assertTrue($testEntity->is($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongOrSelector($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID and tags...
		$resultEntity = $nymph->getEntity(
				array('class' => 'TestModel'),
				array('|', 'guid' => array($testEntity->guid % 1000 + 1, $testEntity->guid % 1000 + 2))
			);
		$this->assertFalse($testEntity->is($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotGUID($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !GUID...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', '!guid' => ($testEntity->guid + 1), 'tag' => 'test')
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'guid' => $testEntity->guid, '!tag' => array('barbecue', 'pickles'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testGUIDAndWrongTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing GUID and wrong tags...
		$resultEntity = $nymph->getEntity(
				array('class' => 'TestModel'),
				array('&', 'guid' => $testEntity->guid, 'tag' => array('pickles'))
			);
		$this->assertEmpty($resultEntity);
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test')
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'pickles')
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testInclusiveTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by tags inclusively...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'tag' => array('pickles', 'test', 'barbecue'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInclusiveTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong inclusive tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'tag' => array('pickles', 'barbecue'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testMixedTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by mixed tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('|', 'tag' => array('pickles', 'test', 'barbecue'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInclusiveMixedTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong inclusive mixed tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('|', 'tag' => array('pickles', 'barbecue'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongExclusiveMixedTags($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong exclusive mixed tags...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'pickles'),
				array('|', 'tag' => array('test', 'barbecue'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testIsset($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by isset...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'isset' => array('string'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotIsset($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !isset...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', '!isset' => array('null'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotIssetOnUnset($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !isset on unset var...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('!&', 'isset' => array('pickles')),
				array('&', 'tag' => 'test')
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testStrict($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by strict...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'strict' => array('string', 'test'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotStrict($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];
		$referenceEntity = TestModel::factory($arr['refGuid']);
		$this->assertSame($arr['refGuid'], $referenceEntity->guid);

		// Retrieving entity by !strict...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', '!strict' => array('string', 'wrong'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$this->assertFalse($referenceEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'data' => array('string', 'test'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];
		$referenceEntity = TestModel::factory($arr['refGuid']);
		$this->assertSame($arr['refGuid'], $referenceEntity->guid);

		// Retrieving entity by !data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', '!data' => array('string', 'wrong'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$this->assertFalse($referenceEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testLike($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'like' => array('string', 't_s%'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotLike($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];
		$referenceEntity = TestModel::factory($arr['refGuid']);
		$this->assertSame($arr['refGuid'], $referenceEntity->guid);

		// Retrieving entity by !data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', '!like' => array('string', 'wr_n%'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$this->assertFalse($referenceEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testDataInclusive($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by data inclusively...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'data' => array(array('string', 'test'), array('string', 'pickles')))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @expectedException PHPUnit_Framework_Error_Notice
	 * @depends testCreateEntity
	 */
	public function testNotDataInclusive($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !data inclusively...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('!|', 'data' => array(array('name', $testEntity->name), array('string', 'pickles'))),
				array('|', '!data' => array(array('name', $testEntity->name), array('string', 'pickles')))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'data' => array('string', 'pickles'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testTagsAndData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by tags and data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'data' => array('string', 'test'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongTagsRightData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong tags and right data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'pickles', 'data' => array('string', 'test'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testRightTagsWrongData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing right tags and wrong data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'data' => array('string', 'pickles'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongTagsWrongData($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong tags and wrong data...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'pickles', 'data' => array('string', 'pickles'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testArrayValue($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by array value...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'array' => array('array', 'values'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @expectedException PHPUnit_Framework_Error_Notice
	 * @depends testCreateEntity
	 */
	public function testNotArrayValue($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !array value...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('!&', 'array' => array('array', 'pickles'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongArrayValue($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong array value...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'array' => array('array', 'pickles'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPCRE($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by regex match...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'match' => array('match', '/.*/')) // anything
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'match' => array('match', '/Edward McCheese/')) // a substring
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('|', 'match' => array(array('string', '/\d/'), array('match', '/Edward McCheese/'))) // inclusive test
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'match' => array('match', '/\b[\w\-+]+@[\w-]+\.\w{2,4}\b/')) // a simple email
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'match' => array('match', '/\(\d{3}\)\s\d{3}-\d{4}/')) // a phone number
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongPCRE($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong regex match...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'match' => array('match', '/Q/'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'pickle', 'match' => array('match', '/.*/'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'match' => array(array('string', '/\d/'), array('match', '/,,/')))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPCREAndDataInclusive($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by regex + data inclusively...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('|', 'data' => array('string', 'pickles'), 'match' => array('string', '/test/'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPosixRegex($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by regex match...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'pmatch' => array('match', '.*')) // anything
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'pmatch' => array('match', 'Edward McCheese')) // a substring
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('|', 'pmatch' => array(array('string', '[0-9]'), array('match', 'Edward McCheese'))) // inclusive test
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'pmatch' => array('match', '[[:<:]][a-zA-Z0-9+_-]+@[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]{2,4}[[:>:]]')) // a simple email
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'pmatch' => array('match', '\([0-9]{3}\) [0-9]{3}-[0-9]{4}')) // a phone number
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongPosixRegex($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong regex match...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'match' => array('pmatch', 'Q'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'pickle', 'pmatch' => array('match', '.*'))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'pmatch' => array(array('string', '[0-9]'), array('match', ',,')))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPosixRegexAndDataInclusive($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by regex + data inclusively...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('|', 'data' => array('string', 'pickles'), 'pmatch' => array('string', 'test'))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testInequality($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by inequality...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'gte' => array(array('number', 30), array('pickles', 100)))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @expectedException PHPUnit_Framework_Error_Notice
	 * @depends testCreateEntity
	 */
	public function testNotInequality($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !inequality...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('!&', 'gte' => array('number', 60))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInequality($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong inequality...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'lte' => array('number', 29.99))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testCDate($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by time...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'gt' => array('cdate', $testEntity->cdate - 120))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongCDate($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong time...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test', 'gte' => array('cdate', $testEntity->cdate + 1))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testReferences($arr) {
		$testEntity = $arr['entity'];

		// Testing referenced entities...
		$this->assertSame('good', $testEntity->reference->test);

		// Testing referenced entity arrays...
		$this->assertSame('good', $testEntity->ref_array[0]['entity']->test);
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'ref' => array('reference', $arr['refGuid']))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by !reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'tag' => 'test'),
				array('!&', 'ref' => array('reference', $arr['refGuid'] + 1))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'ref' => array('reference', $arr['refGuid'] + 1))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNonexistentReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing non-existent reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'ref' => array('pickle', $arr['refGuid']))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testInclusiveReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by inclusive reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'ref' => array(array('reference', $arr['refGuid']), array('reference', $arr['refGuid'] + 1)))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInclusiveReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong inclusive reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('|', 'ref' => array(array('reference', $arr['refGuid'] + 2), array('reference', $arr['refGuid'] + 1)))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testArrayReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Retrieving entity by array reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'ref' => array('ref_array', $arr['refGuid']))
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongArrayReference($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing wrong array reference...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&', 'ref' => array(array('ref_array', $arr['refGuid']), array('ref_array', $arr['refGuid'] + 1)))
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testLogicOperations($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		// Testing logic operations...
		$resultEntity = $nymph->getEntities(
				array('class' => 'TestModel'),
				array('&',
					'!ref' => array(
						array('ref_array', $arr['refGuid'] + 1),
						array('ref_array', $arr['refGuid'] + 2)
					),
					'!lte' => array('number', 29.99)
				),
				array('|',
					'!lte' => array(
						array('number', 29.99),
						array('number', 30)
					)
				),
				array('!&',
					'!strict' => array('string', 'test'),
					'!array' => array(
						array('array', 'full'),
						array('array', 'of'),
						array('array', 'values'),
						array('array', 500)
					)
				),
				array('!|',
					'!strict' => array('string', 'test'),
					'array' => array(
						array('array', 'full'),
						array('array', 'of'),
						array('array', 'values'),
						array('array', 500)
					)
				)
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testDeleteReference($arr) {
		$testEntity = $arr['entity'];

		// Deleting referenced entities...
		$this->assertTrue($testEntity->reference->delete());
		$this->assertNull($testEntity->reference->guid);
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testDelete($arr) {
		$nymph = $arr['nymph'];
		$testEntity = $arr['entity'];

		$guid = $testEntity->guid;

		// Deleting entity...
		$this->assertTrue($testEntity->delete());
		$this->assertNull($testEntity->guid);

		$entity = $nymph->getEntity(array('class' => 'TestModel'), array('&', 'guid' => $guid));

		$this->assertNull($entity);
	}
}
