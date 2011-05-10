<?php
/* Households Test cases generated on: 2010-07-12 10:07:39 : 1278957279 */
App::import('Lib', 'CoreTestCase');
App::import('Component', array('QueueEmail.QueueEmail', 'Notifier'));
App::import('Controller', 'Households');

Mock::generatePartial('QueueEmailComponent', 'MockQueueEmailComponent', array('_smtp', '_mail'));
Mock::generatePartial('NotifierComponent', 'MockNotifierComponent', array('_render'));
Mock::generatePartial('HouseholdsController', 'TestHouseholdsController', array('isAuthorized', 'render', 'redirect', '_stop', 'header'));

class HouseholdsControllerTestCase extends CoreTestCase {

	function startTest() {
		$this->loadFixtures('Household', 'HouseholdMember', 'User', 'Profile', 'Group');
		$this->Households =& new TestHouseholdsController();
		$this->Households->__construct();
		$this->Households->constructClasses();
		$this->Households->Component->initialize($this->Households);
		$this->Households->Notifier->QueueEmail = new MockQueueEmailComponent();
		$this->Households->Notifier->initialize($this->Households);
		$this->Households->setReturnValue('isAuthorized', true);
		$this->Households->Notifier->QueueEmail->setReturnValue('_smtp', true);
		$this->Households->Notifier->QueueEmail->setReturnValue('_mail', true);
		$this->testController = $this->Households;
	}

	function endTest() {
		$this->Households->Session->destroy();
		unset($this->Households);		
		ClassRegistry::flush();
	}

	function testConfirm() {
		$this->testAction('/households/confirm/6/6');
		$results = $this->Households->Household->HouseholdMember->read(null, 6);
		$this->assertTrue($results['HouseholdMember']['confirmed']);
	}

	function testShiftHousehold() {
		$this->testAction('/households/shift_households/1/1/User:1');
		$householdMember = $this->Households->Household->HouseholdMember->find('all', array(
			'conditions' => array(
				'HouseholdMember.user_id' => 1
			)
		));
		$results = $this->Households->Household->getHouseholdIds(1);
		sort($results);
		$expected = array(1);
		$this->assertEqual($results, $expected);

		$this->testAction('/households/shift_households/1/2/User:1');
		$householdMember = $this->Households->Household->HouseholdMember->find('all', array(
			'conditions' => array(
				'HouseholdMember.user_id' => 1
			)
		));
		$results = $this->Households->Household->getHouseholdIds(1);
		sort($results);
		$expected = array(1, 2);
		$this->assertEqual($results, $expected);
	}

	function testMakeHouseholdContact() {
		// have to make them a member first
		$this->Households->Household->HouseholdMember->save(array(
			'user_id' => 1,
			'household_id' => 2,
			'confirmed' => true
		));
		$this->testAction('/households/make_household_contact/1/2/User:1');
		$results = $this->Households->Household->find('all', array(
			'fields' => array('id', 'contact_id')
		));
		$expected = array(
			array(
				'Household' => array(
					'id' => 1,
					'contact_id' => 1
				)
			),
			array(
				'Household' => array(
					'id' => 2,
					'contact_id' => 1
				)
			),
			array(
				'Household' => array(
					'id' => 3,
					'contact_id' => 3
				)
			)
		);
		$this->assertEqual($results, $expected);
	}

	function testIndex() {
		$vars = $this->testAction('/households/index/User:1', array(
			'return' => 'vars'
		));
		$results = Set::extract('/Household/id', $vars['households']);
		$expected = array(1);
		$this->assertEqual($results, $expected);

		$this->Households->Household->HouseholdMember->save(array(
			'user_id' => 1,
			'household_id' => 2,
			'confirmed' => true
		));
		$vars = $this->testAction('/households/index/User:1', array(
			'return' => 'vars'
		));
		$results = Set::extract('/Household/id', $vars['households']);
		$expected = array(1, 2);
		$this->assertEqual($results, $expected);
	}

}
?>