<?php
/**
 * Alert controller class.
 *
 * @copyright     Copyright 2010, *ROCK*HARBOR
 * @link          http://rockharbor.org *ROCK*HARBOR
 * @package       core
 * @subpackage    core.app.controllers
 */

/**
 * Alerts Controller
 *
 * @package       core
 * @subpackage    core.app.controllers
 */
class AlertsController extends AppController {

/**
 * The name of the controller
 *
 * @var string
 */
	var $name = 'Alerts';

/**
 * Extra components for this controller
 *
 * @var array
 */
	var $components = array('MultiSelect.MultiSelect');

/**
 * Extra helpers for this controller
 *
 * @var array
 */
	var $helpers = array('MultiSelect.MultiSelect', 'Formatting');

/**
 * Model::beforeFilter() callback
 *
 * Used to override Acl permissions for this controller.
 *
 * @access private
 */ 
	function beforeFilter() {
		parent::beforeFilter();
	}

/**
 * List of all Alerts
 */
	function index() {
		$this->Alert->recursive = 0;
		$this->set('alerts', $this->paginate());
	}
	
/**
 * Views an alert
 *
 * @param integer $id The id of the alert to read
 */ 	
	function view($id = null) {
		$groups = $this->Alert->Group->findGroups($this->activeUser['Group']['id']);
		$alert = $this->Alert->find('first', array(
			'conditions' => array(
				'Alert.id' => $id,
				'Alert.group_id' => array_keys($groups)
			)
		));

		if (empty($alert)) {
			$this->Session->setFlash('You cannot view that alert', 'flash'.DS.'failure');
			$this->redirect(array('action'=>'history'));
		}
		
		$this->set(compact('alert')); 
	}

/**
 * History of alerts for a user
 *
 * @param string $unread Search filter (`read`, `unread`, empty for all)
 */ 
	function history($unread = '') {
		$userId = $this->activeUser['User']['id'];
		$groups = $this->Alert->Group->findGroups($this->activeUser['Group']['id']);
		
		switch ($unread) {			
			case 'unread':
			$this->paginate = array(
				'conditions' => array(
					'Alert.id' => $this->Alert->getUnreadAlerts($userId, array_keys($groups))
				),
				'order' => 'Alert.created DESC'
			);
			break;
			case 'read':
			$this->paginate = array(
				'conditions' => array(
					'Alert.id' => $this->Alert->getReadAlerts($userId)
				),
				'order' => 'Alert.created DESC'
			);
			break;
			default:
			$this->paginate = array(
				'conditions' => array(
					'Alert.group_id' => array_keys($groups)
				),
				'order' => 'Alert.created DESC'
			);
			break;
		}
		
		$this->MultiSelect->saveSearch($this->paginate);
		$this->set('read', $this->Alert->getReadAlerts($userId));		
		$this->set('alerts', $this->paginate());
	}

/**
 * Marks an alert as `read` for the user
 *
 * @param integer $id The alert id
 */
	function read($id = null) {
		$userId = $this->activeUser['User']['id'];
		
		if (!$id) {
			$this->Session->setFlash('Could not mark alert as read', 'flash'.DS.'failure');
			$this->redirect(array('action'=>'history'));
		}
		
		// check to see if this is a MultiSelect
		if ($this->MultiSelect->check($id)) {
			$search = $this->MultiSelect->getSearch($id);
			$ids = $this->MultiSelect->getSelected($id);
		} else {
			$ids = array($id);
		}
		
		foreach ($ids as $id) {
			if ($this->Alert->markAsRead($userId, $id)) {
				$this->Session->setFlash('Alert marked as read', 'flash'.DS.'success');
			} else {
				$this->Session->setFlash('Could not mark alert as read', 'flash'.DS.'failure');
			}
		}
	
		$this->redirect(array('action' => 'history', 'both'));
	}

/**
 * Adds an Alert
 */ 
	function add() {
		if (!empty($this->data)) {
			$this->Alert->create();
			if ($this->Alert->save($this->data)) {
				$this->Session->setFlash('The alert has been saved', 'flash'.DS.'success');
				$this->redirect(array('action' => 'index'), null, null, true);
			} else {
				$this->Session->setFlash('Could not save the alert', 'flash'.DS.'failure');
			}
		}
		
		$this->set('groups', $this->Alert->Group->find('list', 
			array(
				'conditions' => array(
					'Group.conditional' => false
				)
			)
		));
	}

/**
 * Edits an Alert
 */ 
	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash('Invalid alert', 'flash'.DS.'failure');
			$this->redirect(array('action' => 'index'));
		}
		if (!empty($this->data)) {
			if ($this->Alert->save($this->data)) {
				$this->Session->setFlash('The alert has been saved', 'flash'.DS.'success');
			} else {
				$this->Session->setFlash('Could not save the alert', 'flash'.DS.'failure');
			}
		}
		if (empty($this->data)) {
			$this->data = $this->Alert->read(null, $id);
		}
		
		$this->set('groups', $this->Alert->Group->find('list', 
			array(
				'conditions' => array(
					'Group.conditional' => false
				)
			)
		));
	}

/**
 * Deletes an Alert
 */ 
	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash('Invalid alert', 'flash'.DS.'failure');
			$this->redirect(array('action'=>'index'));
		}
		if ($this->Alert->delete($id)) {
			$this->Session->setFlash('Alert deleted', 'flash'.DS.'success');
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash('Could not delete alert', 'flash'.DS.'failure');
		$this->redirect(array('action' => 'index'));
	}
}
?>