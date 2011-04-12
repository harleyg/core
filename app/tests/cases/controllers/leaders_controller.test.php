<?php
/* Leaders Test cases generated on: 2010-07-14 12:07:47 : 1279136267 */
App::import('Lib', 'CoreTestCase');
App::import('Component', array('QueueEmail.QueueEmail', 'Notifier'));
App::import('Controller', array('InvolvementLeaders', 'MinistryLeaders', 'CampusLeaders'));

Mock::generatePartial('QueueEmailComponent', 'MockQueueEmailComponent', array('_smtp', '_mail'));
Mock::generatePartial('NotifierComponent', 'MockNotifierComponent', array('_render'));
Mock::generatePartial('InvolvementLeadersController', 'MockInvolvementLeadersController', array('isAuthorized', 'render', 'redirect', '_stop', 'header'));
Mock::generatePartial('MinistryLeadersController', 'MockMinistryLeadersController', array('isAuthorized', 'render', 'redirect', '_stop', 'header'));
Mock::generatePartial('CampusLeadersController', 'MockCampusLeadersController', array('isAuthorized', 'render', 'redirect', '_stop', 'header'));

class LeadersControllerTestCase extends CoreTestCase {

	function _setLeaderController($name = 'Involvement') {
		if (class_exists('Mock'.$name.'LeadersController')) {
			$className = 'Mock'.$name.'LeadersController';
			$this->Leaders =& new $className;
			$this->Leaders->__construct();
			$this->Leaders->constructClasses();
			$this->Leaders->Component->initialize($this->Leaders);
			$this->Leaders->Notifier = new MockNotifierComponent();
			$this->Leaders->Notifier->initialize($this->Leaders);
			$this->Leaders->Notifier->setReturnValue('_render', 'Notification body text');
			$this->Leaders->Notifier->QueueEmail = new MockQueueEmailComponent();
			$this->Leaders->Notifier->QueueEmail->setReturnValue('_smtp', true);
			$this->Leaders->Notifier->QueueEmail->setReturnValue('_mail', true);
			$this->testController = $this->Leaders;
		}
	}

	function startTest() {
		$this->loadFixtures('Leader', 'User', 'Profile', 'Involvement', 'Notification', 'Group', 'Ministry', 'Campus');
		$this->_setLeaderController();
	}

	function endTest() {
		unset($this->Leaders);
		ClassRegistry::flush();
	}

	function testDashboard() {
		$vars = $this->testAction('involvement_leaders/dashboard/User:1');
		$results = Set::extract('/Leader/id', $vars['leaders']);
		sort($results);
		$this->assertEqual($results, array(2, 5));

		$vars = $this->testAction('involvement_leaders/dashboard/User:100');
		$results = Set::extract('/Leader/id', $vars['leaders']);
		sort($results);
		$this->assertEqual($results, array());

		$this->_setLeaderController('Ministry');
		$vars = $this->testAction('ministry_leaders/dashboard/User:2');
		$results = Set::extract('/Leader/id', $vars['leaders']);
		sort($results);
		$this->assertEqual($results, array(4));

		$this->_setLeaderController('Campus');
		$vars = $this->testAction('campus_leaders/dashboard/User:1');
		$results = Set::extract('/Leader/id', $vars['leaders']);
		sort($results);
		$this->assertEqual($results, array(3));

		$this->loadFixtures('Role');
		$this->_setLeaderController('Ministry');
		$vars = $this->testAction('ministry_leaders/dashboard/User:1');
		$results = Set::extract('/Role/id', $vars['leaders']);
		sort($results);
		$this->assertEqual($results, array(1,2));
	}

	function testIndex() {
		$vars = $this->testAction('involvement_leaders/index/Involvement:1', array(
			'return' => 'vars'
		));
		$results = Set::extract('/Leader', $vars['leaders']);
		$expected = array(
			array(
				'Leader' => array(
					'id' => 2,
					'user_id' => 1,
					'model' => 'Involvement',
					'model_id' => 1,
					'created' => '2010-04-09 07:28:57',
					'modified' => '2010-04-09 07:28:57'
				)
			)
		);
		$this->assertEqual($results, $expected);
	}

	function testAdd() {		
		$vars = $this->testAction('/involvement_leaders/add/Involvement:1/model:Involvement/leader:2', array(
			'return' => 'vars'
		));
		$this->assertEqual($vars['type'], 'leading');
		$this->assertEqual($vars['itemType'], 'Involvement');
		$this->assertEqual($vars['itemName'], 'CORE 2.0 testing');
		$this->assertEqual($vars['name'], 'ricky rockharbor');

		$results = $this->Leaders->Leader->User->Notification->find('count');
		$this->assertEqual($results, 7);
	}

	function testDelete() {
		$vars = $this->testAction('/involvement_leaders/delete/Involvement:1/User:1');
		$results = $this->Leaders->Leader->User->Notification->find('count');
		$this->assertEqual($results, 6);
	}

}
?>