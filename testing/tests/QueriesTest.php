<?php
use Nymph\Nymph as Nymph;

class QueriesTest extends PHPUnit_Framework_TestCase {
	public function testInstantiate() {
		$nymph = \SciActive\R::_('Nymph');
		$this->assertInstanceOf('\\Nymph\\Drivers\\DriverInterface', $nymph);
	}

	/**
	 * @expectedException \Nymph\Exceptions\InvalidParametersException
	 */
	public function testInvalidQuery() {
		Nymph::getEntities(
				['class' => 'TestModel'],
				['&',
					'tag' => 'thing'
				],
				[
					'data' => ['this_query', 'should_fail']
				]
			);
	}

	public function testDeleteOldTestData() {
		$all = Nymph::getEntities(['class' => 'TestModel']);
		$this->assertTrue((array)$all===$all);
		foreach ($all as $cur) {
			$this->assertTrue($cur->delete());
		}

		$all = Nymph::getEntities(['class' => 'TestModel']);
		$this->assertEmpty($all);
	}

	/**
	 * @depends testDeleteOldTestData
	 */
	public function testCreateEntity() {
		// Creating entity...
		$testEntity = TestModel::factory();
		$this->assertInstanceOf('TestModel', $testEntity);

		// Saving entity...
		$testEntity->name = 'Entity Test '.time();
		$testEntity->null = null;
		$testEntity->string = 'test';
		$testEntity->array = ['full', 'of', 'values', 500];
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
		$testEntity->ref_array = [0 => ['entity' => $entity_reference_test]];
		$this->assertTrue($testEntity->save());

		$entity_reference_test->test = 'good';
		$this->assertTrue($entity_reference_test->save());

		$testEntity = Nymph::getEntity(['class' => 'TestModel'], $entity_guid);
		$this->assertInstanceOf('TestModel', $testEntity);

		return ['entity' => $testEntity, 'refGuid' => $entity_reference_guid];
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testByGuid($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID...
		$resultEntity = Nymph::getEntity(['class' => 'TestModel'], $testEntity->guid);
		$this->assertTrue($testEntity->is($resultEntity));

		// Using class constructor...
		$resultEntity = TestModel::factory($testEntity->guid);
		$this->assertTrue($testEntity->is($resultEntity));

		// Testing wrong GUID...
		$resultEntity = Nymph::getEntity(['class' => 'TestModel'], $testEntity->guid + 1);
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
		$testEntity = $arr['entity'];

		// Testing entity order, offset, limit...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel', 'reverse' => true, 'offset' => 1, 'limit' => 1, 'sort' => 'cdate'],
				['&', 'tag' => 'test']
			);
		$this->assertTrue($testEntity->is($resultEntity[0]));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testGUIDAndTags($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID and tags...
		$resultEntity = Nymph::getEntity(
				['class' => 'TestModel'],
				['&', 'guid' => $testEntity->guid, 'tag' => 'test']
			);
		$this->assertTrue($testEntity->is($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testOrSelector($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID and tags...
		$resultEntity = Nymph::getEntity(
				['class' => 'TestModel'],
				['|', 'guid' => [$testEntity->guid, $testEntity->guid % 1000 + 1]]
			);
		$this->assertTrue($testEntity->is($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongOrSelector($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by GUID and tags...
		$resultEntity = Nymph::getEntity(
				['class' => 'TestModel'],
				['|', 'guid' => [$testEntity->guid % 1000 + 1, $testEntity->guid % 1000 + 2]]
			);
		$this->assertFalse($testEntity->is($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotGUID($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !GUID...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', '!guid' => ($testEntity->guid + 1), 'tag' => 'test']
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotTags($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'guid' => $testEntity->guid, '!tag' => ['barbecue', 'pickles']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testGUIDAndWrongTags($arr) {
		$testEntity = $arr['entity'];

		// Testing GUID and wrong tags...
		$resultEntity = Nymph::getEntity(
				['class' => 'TestModel'],
				['&', 'guid' => $testEntity->guid, 'tag' => ['pickles']]
			);
		$this->assertEmpty($resultEntity);
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testTags($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test']
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongTags($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'pickles']
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testInclusiveTags($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by tags inclusively...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'tag' => ['pickles', 'test', 'barbecue']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInclusiveTags($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong inclusive tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'tag' => ['pickles', 'barbecue']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testMixedTags($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by mixed tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['|', 'tag' => ['pickles', 'test', 'barbecue']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInclusiveMixedTags($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong inclusive mixed tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['|', 'tag' => ['pickles', 'barbecue']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongExclusiveMixedTags($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong exclusive mixed tags...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'pickles'],
				['|', 'tag' => ['test', 'barbecue']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testIsset($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by isset...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'isset' => ['string']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotIsset($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !isset...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', '!isset' => ['null']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotIssetOnUnset($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !isset on unset var...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['!&', 'isset' => ['pickles']],
				['&', 'tag' => 'test']
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testStrict($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by strict...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'strict' => ['string', 'test']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotStrict($arr) {
		$testEntity = $arr['entity'];
		$referenceEntity = TestModel::factory($arr['refGuid']);
		$this->assertSame($arr['refGuid'], $referenceEntity->guid);

		// Retrieving entity by !strict...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', '!strict' => ['string', 'wrong']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$this->assertFalse($referenceEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testData($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'data' => ['string', 'test']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotData($arr) {
		$testEntity = $arr['entity'];
		$referenceEntity = TestModel::factory($arr['refGuid']);
		$this->assertSame($arr['refGuid'], $referenceEntity->guid);

		// Retrieving entity by !data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', '!data' => ['string', 'wrong']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$this->assertFalse($referenceEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testLike($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'like' => ['string', 't_s%']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotLike($arr) {
		$testEntity = $arr['entity'];
		$referenceEntity = TestModel::factory($arr['refGuid']);
		$this->assertSame($arr['refGuid'], $referenceEntity->guid);

		// Retrieving entity by !data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', '!like' => ['string', 'wr_n%']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$this->assertFalse($referenceEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testDataInclusive($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by data inclusively...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'data' => [['string', 'test'], ['string', 'pickles']]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotDataInclusive($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !data inclusively...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['!|', 'data' => [['name', $testEntity->name], ['string', 'pickles']]],
				['|', '!data' => [['name', $testEntity->name], ['string', 'pickles']]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongData($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'data' => ['string', 'pickles']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testTagsAndData($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by tags and data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'data' => ['string', 'test']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongTagsRightData($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong tags and right data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'pickles', 'data' => ['string', 'test']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testRightTagsWrongData($arr) {
		$testEntity = $arr['entity'];

		// Testing right tags and wrong data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'data' => ['string', 'pickles']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongTagsWrongData($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong tags and wrong data...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'pickles', 'data' => ['string', 'pickles']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testArrayValue($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by array value...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'array' => ['array', 'values']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotArrayValue($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !array value...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['!&', 'array' => ['array', 'pickles']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongArrayValue($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong array value...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'array' => ['array', 'pickles']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPCRE($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by regex match...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'match' => ['match', '/.*/']] // anything
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'match' => ['match', '/Edward McCheese/']] // a substring
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['|', 'match' => [['string', '/\d/'], ['match', '/Edward McCheese/']]] // inclusive test
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'match' => ['match', '/\b[\w\-+]+@[\w-]+\.\w{2,4}\b/']] // a simple email
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'match' => ['match', '/\(\d{3}\)\s\d{3}-\d{4}/']] // a phone number
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongPCRE($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong regex match...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'match' => ['match', '/Q/']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'pickle', 'match' => ['match', '/.*/']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'match' => [['string', '/\d/'], ['match', '/,,/']]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPCREAndDataInclusive($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by regex + data inclusively...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['|', 'data' => ['string', 'pickles'], 'match' => ['string', '/test/']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPosixRegex($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by regex match...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'pmatch' => ['match', '.*']] // anything
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'pmatch' => ['match', 'Edward McCheese']] // a substring
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['|', 'pmatch' => [['string', '[0-9]'], ['match', 'Edward McCheese']]] // inclusive test
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'pmatch' => ['match', '[[:<:]][a-zA-Z0-9+_-]+@[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]{2,4}[[:>:]]']] // a simple email
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'pmatch' => ['match', '\([0-9]{3}\) [0-9]{3}-[0-9]{4}']] // a phone number
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongPosixRegex($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong regex match...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'match' => ['pmatch', 'Q']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'pickle', 'pmatch' => ['match', '.*']]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'pmatch' => [['string', '[0-9]'], ['match', ',,']]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testPosixRegexAndDataInclusive($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by regex + data inclusively...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['|', 'data' => ['string', 'pickles'], 'pmatch' => ['string', 'test']]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testInequality($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by inequality...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'gte' => [['number', 30], ['pickles', 100]]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotInequality($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !inequality...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['!&', 'gte' => ['number', 60]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInequality($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong inequality...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'lte' => ['number', 29.99]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testCDate($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by time...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'gt' => ['cdate', $testEntity->cdate - 120]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongCDate($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong time...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test', 'gte' => ['cdate', $testEntity->cdate + 1]]
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
		$testEntity = $arr['entity'];

		// Retrieving entity by reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'ref' => ['reference', $arr['refGuid']]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNotReference($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by !reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'tag' => 'test'],
				['!&', 'ref' => ['reference', $arr['refGuid'] + 1]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongReference($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'ref' => ['reference', $arr['refGuid'] + 1]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testNonexistentReference($arr) {
		$testEntity = $arr['entity'];

		// Testing non-existent reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'ref' => ['pickle', $arr['refGuid']]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testInclusiveReference($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by inclusive reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'ref' => ['reference', [$arr['refGuid'], $arr['refGuid'] + 1]]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));

		// Retrieving entity by inclusive reference... (slower query when written like this.)
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'ref' => [['reference', $arr['refGuid']], ['reference', $arr['refGuid'] + 1]]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongInclusiveReference($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong inclusive reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'ref' => ['reference', [$arr['refGuid'] + 2, $arr['refGuid'] + 1]]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));

		// Testing wrong inclusive reference... (slower query when written like this.)
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['|', 'ref' => [['reference', $arr['refGuid'] + 2], ['reference', $arr['refGuid'] + 1]]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testArrayReference($arr) {
		$testEntity = $arr['entity'];

		// Retrieving entity by array reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'ref' => ['ref_array', $arr['refGuid']]]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongArrayReference($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong array reference...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&', 'ref' => [['ref_array', $arr['refGuid']], ['ref_array', $arr['refGuid'] + 1]]]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testLogicOperations($arr) {
		$testEntity = $arr['entity'];

		// Testing logic operations...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&',
					'!ref' => [
						['ref_array', $arr['refGuid'] + 1],
						['ref_array', $arr['refGuid'] + 2]
					],
					'!lte' => ['number', 29.99]
				],
				['|',
					'!lte' => [
						['number', 29.99],
						['number', 30]
					]
				],
				['!&',
					'!strict' => ['string', 'test'],
					'!array' => [
						['array', 'full'],
						['array', 'of'],
						['array', 'values'],
						['array', 500]
					]
				],
				['!|',
					'!strict' => ['string', 'test'],
					'array' => [
						['array', 'full'],
						['array', 'of'],
						['array', 'values'],
						['array', 500]
					]
				]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testDeepSelector($arr) {
		$testEntity = $arr['entity'];

		// Testing deep selectors...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&',
					'!ref' => [
						['ref_array', $arr['refGuid'] + 1],
						['ref_array', $arr['refGuid'] + 2]
					],
					'!lte' => ['number', 29.99]
				],
				['&',
					['|',
						'!lte' => [
							['number', 29.99],
							['number', 30]
						]
					],
					['!&',
						'!strict' => ['string', 'test'],
						'!array' => [
							['array', 'full'],
							['array', 'of'],
							['array', 'values'],
							['array', 500]
						]
					],
					['!|',
						'!strict' => ['string', 'test'],
						'array' => [
							['array', 'full'],
							['array', 'of'],
							['array', 'values'],
							['array', 500]
						]
					]
				]
			);
		$this->assertTrue($testEntity->inArray($resultEntity));

		$resultEntity2 = Nymph::getEntities(
				['class' => 'TestModel'],
				['&',
					'!ref' => [
						['ref_array', $arr['refGuid'] + 1],
						['ref_array', $arr['refGuid'] + 2]
					],
					'!lte' => ['number', 29.99]
				],
				['|',
					['&',
						'!lte' => [
							['number', 29.99],
							['number', 30]
						]
					],
					['!&',
						'!strict' => ['string', 'test'],
						'!array' => [
							['array', 'full'],
							['array', 'of'],
							['array', 'values'],
							['array', 500]
						]
					],
					['&',
						'!strict' => ['string', 'test'],
						'array' => [
							['array', 'full'],
							['array', 'of'],
							['array', 'values'],
							['array', 500]
						]
					]
				]
			);
		$this->assertTrue($testEntity->inArray($resultEntity2));

		$resultEntity3 = Nymph::getEntities(
				['class' => 'TestModel'],
				['|',
					['&',
						'!ref' => ['ref_array', $arr['refGuid'] + 2],
						'!lte' => ['number', 29.99]
					],
					['&',
						'gte' => ['number', 16000]
					]
				]
			);
		$this->assertTrue($testEntity->inArray($resultEntity3));

		$resultEntity4 = Nymph::getEntities(
				['class' => 'TestModel'],
				['|',
					['&',
						'!ref' => ['ref_array', $arr['refGuid'] + 2],
						'!lte' => ['number', 29.99]
					],
					['&',
						['&',
							['&',
								'gte' => ['number', 16000]
							]
						]
					]
				]
			);
		$this->assertTrue($testEntity->inArray($resultEntity4));
	}

	/**
	 * @depends testCreateEntity
	 */
	public function testWrongDeepSelector($arr) {
		$testEntity = $arr['entity'];

		// Testing wrong deep selectors...
		$resultEntity = Nymph::getEntities(
				['class' => 'TestModel'],
				['&',
					['&',
						'!ref' => ['ref_array', $arr['refGuid'] + 2],
						'!lte' => ['number', 29.99]
					],
					['&',
						'gte' => ['number', 16000]
					]
				]
			);
		$this->assertFalse($testEntity->inArray($resultEntity));
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
		$testEntity = $arr['entity'];

		$guid = $testEntity->guid;

		// Deleting entity...
		$this->assertTrue($testEntity->delete());
		$this->assertNull($testEntity->guid);

		$entity = Nymph::getEntity(['class' => 'TestModel'], ['&', 'guid' => $guid]);

		$this->assertNull($entity);
	}
}
