<?php
/* Households Test cases generated on: 2010-07-12 10:07:39 : 1278957279 */
App::import('Controller', 'Households');

class TestHouseholdsController extends HouseholdsController {
	function redirect($url, $status = null, $exit = true) {
		$this->redirectUrl = $url;
	}

	function _stop($status = 0) {
		$this->stopped = $status;
	}
}

class HouseholdsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.notification', 'app.user', 'app.group', 'app.profile', 'app.classification', 'app.job_category', 'app.school', 'app.campus', 'plugin.media.attachment', 'app.ministry', 'app.involvement', 'app.involvement_type', 'app.address', 'app.zipcode', 'app.region', 'app.date', 'app.payment_option', 'app.question', 'app.roster', 'app.role', 'app.roster_status', 'app.answer', 'app.payment', 'app.payment_type', 'app.leader', 'app.comment', 'app.comment_type', 'app.comments', 'app.notification', 'app.image', 'plugin.media.document', 'app.household_member', 'app.household', 'app.publication', 'app.publications_user', 'app.log', 'app.app_setting', 'app.alert', 'app.alerts_user', 'app.log');

/**
 * Disable inserting all records by default. Use CakeTestCase::loadFixtures
 * to load the data needed for the test (or case).
 */
	var $autoFixtures = false;

	function startTest() {
		$this->loadFixtures('Household', 'HouseholdMember', 'User', 'Profile');
		$this->Households =& new TestHouseholdsController();
		$this->Households->constructClasses();
		$this->Households->Component->initialize($this->Households);
		$this->Households->Session->write('Auth.User', array('id' => 1));
		$this->Households->Session->write('User', array('Group' => array('id' => 1)));
	}

	function endTest() {
		$this->Households->Session->destroy();
		unset($this->Households);		
		ClassRegistry::flush();
	}

	function testShiftHousehold() {
		$this->testAction('/test_households/shift_households/1/1');
		$householdMember = $this->Households->Household->HouseholdMember->find('all', array(
			'conditions' => array(
				'HouseholdMember.user_id' => 1
			)
		));
		$this->assertEqual($householdMember[0]['HouseholdMember']['id'], 4);
		$this->assertEqual($householdMember[0]['HouseholdMember']['household_id'], 1);

		$this->testAction('/test_households/shift_households/1/2');
		$householdMember = $this->Households->Household->HouseholdMember->find('all', array(
			'conditions' => array(
				'HouseholdMember.user_id' => 1
			)
		));
		$this->assertEqual($householdMember[0]['HouseholdMember']['id'], 4);
		$this->assertEqual($householdMember[1]['HouseholdMember']['id'], 5);
	}

	function testMakeHouseholdContact() {
		// have to make them a member first
		$this->Households->Household->HouseholdMember->save(array(
			'user_id' => 1,
			'household_id' => 2,
			'confirmed' => true
		));
		$this->testAction('/test_households/make_household_contact/1/2');
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
			)
		);
		$this->assertEqual($results, $expected);
	}

	function testIndex() {
		$vars = $this->testAction('/test_households/index/User:1', array(
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
		$vars = $this->testAction('/test_households/index/User:1', array(
			'return' => 'vars'
		));
		$results = Set::extract('/Household/id', $vars['households']);
		$expected = array(1, 2);
		$this->assertEqual($results, $expected);
	}

}
?>