<?php
App::import('Lib', 'CoreTestCase');
App::import('Model', 'Notification');
App::import('Component', array('Notifier', 'QueueEmail.QueueEmail'));

Mock::generatePartial('NotifierComponent', 'MockNotifierComponent', array('_render'));
Mock::generatePartial('QueueEmailComponent', 'MockQueueEmailComponent', array('send'));

class TestNotifierController extends Controller {
	
	public $activeUser = array(
		'Profile' => array(
			'name' => 'Example',
			'primary_email' => 'example@example.com'
		)
	);
	
}

class NotifierTestCase extends CoreTestCase {

	function startTest() {
		$this->loadFixtures('Notification', 'User', 'Profile', 'Queue');
		$this->loadSettings();
		$this->Notification = ClassRegistry::init('Notification');
		$this->Controller = new TestNotifierController();
		$this->Notifier = new MockNotifierComponent();
		$this->Notifier->setReturnValue('_render', 'A notification');
		$this->Notifier->initialize($this->Controller, array());		
		$this->Notifier->QueueEmail = new MockQueueEmailComponent();
		$this->Notifier->QueueEmail->setReturnValue('send', true);
	}

	function endTest() {
		$this->unloadSettings();
		unset($this->Notifier);
		unset($this->Notification);
		unset($this->Controller);
		ClassRegistry::flush();
	}

	function testNotify() {
		$this->assertTrue($this->Notifier->notify(array(
			'to' => 1,
			'template' => 'ministries_edit'
		)));
		$this->assertFalse($this->Notifier->notify(array(
			'to' => 100,
			'template' => 'ministries_edit'
		)));
	}

	function testSend() {
		$this->Notification->User->contain(array('Profile'));
		$user = $this->Notification->User->read(null, 1);

		$this->assertTrue($this->Notifier->_send($user));
		$expected = 'CORE <core@rockharbor.org>';
		$this->assertEqual($this->Notifier->QueueEmail->from, $expected);

		$this->assertTrue($this->Notifier->_send($user, array('from' => 2)));
		$expected = 'ricky rockharbor <ricky@rockharbor.org>';
		$this->assertEqual($this->Notifier->QueueEmail->from, $expected);

		$this->Notifier->_send($user, array(
			'from' => 2,
			'attachments' => array(
				'/path/to/file.txt'
			)
		));
		$expected = array('/path/to/file.txt');
		$this->assertEqual($this->Notifier->QueueEmail->attachments, $expected);
	}

	function testSave() {
		$this->Notification->User->contain(array('Profile'));
		$user = $this->Notification->User->read(null, 1);

		$data = array(
			'template' => 'leaders_add'
		);
		$this->Notifier->_save($user, $data);
		$this->Notification->recursive = -1;
		$result = $this->Notification->read(array('user_id', 'read', 'type'));
		$expected = array(
			'Notification' => array(
				'user_id' => 1,
				'read' => false,
				'type' => 'default'
			)
		);
		$this->assertEqual($result, $expected);
		
		$data = array(
			'template' => 'leaders_add',
			'type' => 'invitation'
		);
		$this->Notifier->_save($user, $data);
		$this->Notification->recursive = -1;
		$result = $this->Notification->read(array('user_id', 'read', 'type'));
		$expected = array(
			'Notification' => array(
				'user_id' => 1,
				'read' => false,
				'type' => 'invitation'
			)
		);
		$this->assertEqual($result, $expected);
	}
}
?>