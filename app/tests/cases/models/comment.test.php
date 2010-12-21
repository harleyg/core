<?php
App::import('Lib', 'CoreTestCase');
App::import('Model', 'Comment');

class GroupTestCase extends CoreTestCase {

	function startTest() {
		$this->loadFixtures('Comment', 'Group', 'User');
		$this->Comment =& ClassRegistry::init('Comment');
	}

	function endTest() {
		unset($this->Comment);
		ClassRegistry::flush();
	}

	function testCanDelete() {
		$this->assertFalse($this->Comment->canDelete());
		$this->assertFalse($this->Comment->canDelete(3));
		$this->assertFalse($this->Comment->canDelete(3, 1));
		$this->assertTrue($this->Comment->canDelete(3, 4));
		$this->assertTrue($this->Comment->canDelete(1, 1));
		$this->assertFalse($this->Comment->canDelete(1, 4));
		$this->assertFalse($this->Comment->canDelete(1, 2));
	}

	function testCanEdit() {
		$this->assertTrue($this->Comment->canEdit(1, 1));
		$this->assertFalse($this->Comment->canEdit());
	}

}
?>