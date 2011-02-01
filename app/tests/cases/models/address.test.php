<?php
App::import('Lib', 'CoreTestCase');
App::import('Model', 'Address');

class GroupTestCase extends CoreTestCase {

	function startTest() {
		$this->loadFixtures('Address');
		$this->Address =& ClassRegistry::init('Address');
	}

	function endTest() {
		unset($this->Comment);
		ClassRegistry::flush();
	}

	function testDistance() {
		$this->assertNull($this->Address->distance());
		$this->assertNull($this->Address->distance('123'));
		$result = $this->Address->distance('1', '2');
		$this->assertIsA($result, 'string');
	}

	function testRelated() {
		$results = $this->Address->related(1);
		$this->assertEqual($results, array(2));
		$results = $this->Address->related(100);
		$this->assertFalse($results);
	}

	function testToggleActivity() {
		$result = $this->Address->toggleActivity(3, false);
		$this->assertFalse($result);
		$result = $this->Address->toggleActivity(1, false);
		$this->assertTrue($result);
		$result = $this->Address->toggleActivity(2, false);
		$this->assertFalse($result);
		$result = $this->Address->toggleActivity(4, false);
		$this->assertTrue($result);
		$result = $this->Address->toggleActivity(1, true);
		$this->assertTrue($result);
	}

}
?>