<?php
use Nymph\Nymph as Nymph;

class SortingTest extends PHPUnit_Framework_TestCase {
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

		Nymph::sort($arr, 'name');

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

		Nymph::sort($arr, 'name', true, true);

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

		Nymph::hsort($arr, 'name', 'parent');

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

		Nymph::hsort($arr, 'name', 'parent', true, true);

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
