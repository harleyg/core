<?php
/* MergeRequests Test cases generated on: 2010-07-14 13:07:43 : 1279138963 */
App::import('Controller', 'MergeRequests');
App::import('Model', array('User', 'Profile'));

class TestMergeRequestsController extends MergeRequestsController {
	var $components = array(
		'DebugKit.Toolbar' => array(
			'autoRun' => false
		),
		'Referee.Whistle' => array(
			'enabled' => false
		),
		'QueueEmail' => array(
			'enabled' => false
		)
	);

	function redirect($url, $status = null, $exit = true) {
		if (!$this->Session->check('TestCase.redirectUrl')) {
			$this->Session->write('TestCase.flash', $this->Session->read('Message.flash'));
			$this->Session->write('TestCase.redirectUrl', $url);
		}
	}

	function _stop($status = 0) {
		$this->Session->write('TestCase.stopped', $status);
	}

	function isAuthorized() {
		$action = str_replace('controllers/Test', '', $this->Auth->action());
		$auth = parent::isAuthorized($action);
		$this->Session->write('TestCase.authorized', $auth);
		return $auth;
	}
}

class MergeRequestsControllerTestCase extends CakeTestCase {
	var $fixtures = array('app.notification', 'app.user', 'app.group',
		'app.profile', 'app.classification', 'app.job_category', 'app.school',
		'app.campus', 'plugin.media.attachment', 'app.ministry',
		'app.involvement', 'app.involvement_type', 'app.address', 'app.zipcode',
		'app.region', 'app.date', 'app.payment_option', 'app.question',
		'app.roster', 'app.role', 'app.roster_status', 'app.answer',
		'app.payment', 'app.payment_type', 'app.leader', 'app.comment',
		'app.comment_type', 'app.comments', 'app.notification', 'app.image',
		'plugin.media.document', 'app.household_member', 'app.household',
		'app.publication', 'app.publications_user', 'app.log', 'app.app_setting',
		'app.alert', 'app.alerts_user', 'app.aro', 'app.aco', 'app.aros_aco',
		'app.ministries_rev', 'app.involvements_rev', 'app.error', 'app.log',
		'app.merge_request'
		);

/**
 * Disable inserting all records by default. Use CakeTestCase::loadFixtures
 * to load the data needed for the test (or case).
 */
	var $autoFixtures = false;

	function startTest() {
		$this->MergeRequests =& new TestMergeRequestsController();
		$this->MergeRequests->constructClasses();
		// necessary fixtures
		$this->loadFixtures('Aco', 'Aro', 'ArosAco', 'Group', 'Error');
		$this->loadFixtures('User', 'Profile', 'MergeRequest');
		$this->MergeRequests->Component->initialize($this->MergeRequests);
		$this->MergeRequests->Session->write('Auth.User', array('id' => 1));
		$this->MergeRequests->Session->write('User', array('Group' => array('id' => 1)));
	}

	function endTest() {
		$this->MergeRequests->Session->destroy();
		unset($this->MergeRequests);		
		ClassRegistry::flush();
	}

	function testIndex() {
		$vars = $this->testAction('/test_merge_requests/index/model:User', array(
			'return' => 'vars'
		));
		$results = Set::extract('/MergeRequest', $vars['requests']);
		$expected = array(
			array(
				'MergeRequest' => array(
					'id' => 1,
					'model' => 'User',
					'model_id' => 3,
					'merge_id' => 2,
					'requester_id' => 2,
					'created' => '2010-07-15 00:00:00',
					'modified' => '2010-07-15 00:00:00',
				)
			)
		);
		$this->assertEqual($results, $expected);
		$this->assertEqual($vars['requests'][0]['Source']['id'], 3);
		$this->assertEqual($vars['requests'][0]['Target']['id'], 2);
	}

	function testView() {
		$vars = $this->testAction('/test_merge_requests/view/1', array(
			'return' => 'vars'
		));
		$this->assertTrue(isset($vars['result']['Source']['Profile']));
	}

	function testMerge() {
		$this->Profile =& ClassRegistry::init('Profile');
		$this->User =& ClassRegistry::init('User');
		$this->testAction('/test_merge_requests/merge/1');

		$results = $this->MergeRequests->MergeRequest->find('all');
		$this->assertEqual($results, array());

		$results = $this->User->find('count');
		$this->assertEqual($results, 2);

		$results = $this->Profile->find('count');
		$this->assertEqual($results, 2);

		$this->User->contain(array('Profile'));
		$user = $this->User->read(null, 2);

		$result = $user['User']['username'];
		$this->assertEqual($result, 'rickyrockharbor');

		$result = $user['Profile']['primary_email'];
		$this->assertEqual($result, 'ricky@rockharbor.org');
	}

	function testDelete() {
		$this->Profile =& ClassRegistry::init('Profile');
		$this->User =& ClassRegistry::init('User');
		$this->testAction('/test_merge_requests/delete/1');

		$this->assertFalse($this->MergeRequests->MergeRequest->read(null, 1));

		$result = $this->Profile->find('count');
		$this->assertEqual($result, 2);

		$result = $this->User->find('count');
		$this->assertEqual($result, 2);
	}

}
?>