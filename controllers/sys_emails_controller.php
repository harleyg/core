<?php
/**
 * Sys Email controller class.
 *
 * @copyright     Copyright 2010, *ROCK*HARBOR
 * @link          http://rockharbor.org *ROCK*HARBOR
 * @package       core
 * @subpackage    core.app.controllers
 */

/**
 * SysEmails Controller
 *
 * @package       core
 * @subpackage    core.app.controllers
 */
class SysEmailsController extends AppController {

/**
 * The name of the controller
 *
 * @var string
 */
	var $name = 'SysEmails';

/**
 * Extra helpers for this controller
 *
 * @var array
 */
	var $helpers = array('Formatting', 'MultiSelect.MultiSelect');

/**
 * Extra components for this controller
 *
 * @var array
 */
	var $components = array('MultiSelect.MultiSelect');

/**
 * Model::beforeFilter() callback
 *
 * Used to override Acl permissions for this controller.
 *
 * @access private
 */ 
	function beforeFilter() {
		parent::beforeFilter();
		
		$this->Auth->allow('bug_compose');
		
		// if the user is leading or managing, let them email people
		if ($this->activeUser['Profile']['leading'] > 0 || $this->activeUser['Profile']['managing'] > 0) {
			$this->Auth->allow('compose');
		}
	}

/**
 * Creates a new bug report email
 */ 
	function bug_compose() {
		$this->set('title_for_layout', 'Submit a bug report');
		$User = ClassRegistry::init('User');
		$User->contain(array('Profile'));
		// hardcoded Jeremy Harris
		$jeremy = $User->findByUsername('jharris');
		
		if (!empty($this->data)) {
			$this->SysEmail->set($this->data);
			
			// send it!
			if ($this->SysEmail->validates() && $this->Notifier->notify(array(
				'from' => $this->activeUser['User']['id'], 
				'to' => $jeremy['User']['id'], 
				'queue' => false,
				'subject' => $this->data['SysEmail']['subject'],
						'body' => $this->data['SysEmail']['body']
			), 'email')) {
				$this->Session->setFlash('Your email has been sent.', 'flash'.DS.'success');
			} else {
				$this->Session->setFlash('Unable to send email.', 'flash'.DS.'failure');
			}
		}
		
		$this->set('visitHistory', array_reverse($this->Session->read('CoreDebugPanels.visitHistory')));
		$this->set('toUsers', array($jeremy));
		$this->set('fromUser', $this->activeUser);
		$this->set('cacheuid', false);
		$this->set('showAttachments', false);
		// needed for element
		$this->set('activeUser', $this->activeUser);
		
		if (empty($this->data)) {
			$this->data['SysEmail']['subject'] = 'Bug Report :: [enter short description here]';
			
			App::import('View', 'view');
			$View = new View($this->Controller, false);
			$View->webroot = WEBROOT_DIR;
			$content = $View->element('email' . DS . 'bug_report', $this->viewVars, true);
			
			$this->data['SysEmail']['body'] = $content;
		}
		
		$this->render('compose');
	}

/**
 * Pass-through function to allow regular users to email leaders
 * 
 * @param integer $leaderId The leader id
 */
	function email_leader($leaderId) {
		$Leader = ClassRegistry::init('Leader');
		$user = $Leader->findById($leaderId);
		$this->MultiSelect->saveSearch(array(
			'conditions' => array(
				'User.id' => $user['Leader']['user_id']
			)
		));
		$this->passedArgs['model'] = 'User';
		$this->data['SysEmail']['to'] = $user['Leader']['user_id'];
		$this->setAction('compose');
	}
	
/**
 * Creates a new email
 *
 * Get's list of email addresses from previously cached search results.
 * Allows for an attachment.
 *
 * There are a few different options for mass emails. The first is by simply
 * supplying two passed args, `model` and `$model`. These will run the `getInvolved()`
 * function on the passed model.
 *
 * If a `model` passed arg is sent as well as a multi-select id, all of those
 * id's for that model will be pulled.
 *
 * Additionally, you can pull the 'Leaders' or 'Managers' of specific models by
 * passing the `submodel` passed arg along with the `model` passed arg, where
 * `submodel` is Leader, Roster, Both (Leaders and Rosters), or Manager. Note: 
 * Managers are leaders who are *above* the current model, i.e., Involvement 
 * managers are leaders of that Involvement's Ministry.
 *
 * If no passed args are sent but a multi-select id is, it's assumed that the
 * selections are Users.
 *
 * ### Passed args:
 * - string $model A model to look up
 * - integer [$model] The id of the model
 * - string `submodel` A special key for mass emails, like emailing all of the
 * leaders within a set of involvements. Valid: 'Leader', 'Roster'
 *
 * @param string $uid The unique cache id of the list to pull
 */ 
	function compose($uid = null) {
		if (!$uid) {
			if (!isset($this->passedArgs['mstoken'])) {
				$this->passedArgs['mstoken'] = $this->MultiSelect->_token;
			}
			$uid = $this->passedArgs['mstoken'];
		}
		$User = ClassRegistry::init('User');

		$modelIds = $this->MultiSelect->getSelected($uid);
		if (empty($modelIds)) {
			// if nothing was selected, find all
			$search = $this->MultiSelect->getSearch($uid);
			$results = ClassRegistry::init($this->passedArgs['model'])->find('all', $search);
			$modelIds = Set::extract('/'.$this->passedArgs['model'].'/id', $results);
		}
		$toUsers = $modelIds;
		if (isset($this->passedArgs['model'])) {
			if (isset($this->passedArgs[$this->passedArgs['model']])) {
				$modelIds = array($this->passedArgs[$this->passedArgs['model']]);
			}			
			if (isset($this->passedArgs['submodel'])) {
				switch ($this->passedArgs['submodel']) {
					case 'Both':
						$Model = ClassRegistry::init($this->passedArgs['model']);
						$invLeaders = $Model->getLeaders($modelIds);
						$invUsers = $Model->getInvolved($modelIds);
						$toUsers = array_merge($invLeaders, $invUsers);
					break;
					case 'Leader':
						$toUsers = ClassRegistry::init($this->passedArgs['model'])->getLeaders($modelIds);
					break;
					default:
						$toUsers = ClassRegistry::init($this->passedArgs['model'])->getInvolved($modelIds);
					break;
				}
			} else {
				switch ($this->passedArgs['model']) {
					case 'User':
						$toUsers = $modelIds;
					break;
					case 'Roster':
						$rosters = ClassRegistry::init('Roster')->find('all', array(
							'conditions' => array(
								'id' => $modelIds
							)
						));
						$toUsers = Set::extract('/Roster/user_id', $rosters);
					break;
					default:
						$toUsers = ClassRegistry::init($this->passedArgs['model'])->getInvolved($this->passedArgs[$this->passedArgs['model']]);
					break;
				}
			}
		}
		
		if (empty($this->data['SysEmail']['to'])) {
			$this->data['SysEmail']['to'] = implode(',', $toUsers);
		}

		$fromUser = $this->activeUser['User']['id'];
		
		if (!empty($this->data) && !empty($this->data['SysEmail']['to'])) {
			// get attachments for this email
			$Document = ClassRegistry::init('Document');
			$Document->recursive = -1;
			$documents = $Document->find('all', array(
				'conditions' => array(
					'foreign_key' => $uid,
					'model' => 'SysEmail'
				)
			));
			
			$attachments = array();
			foreach ($documents as $attachment) {
				list($filename, $ext) = explode('.', $attachment['Document']['basename']);
				$attachments[$attachment['Document']['alternative'].'.'.$ext] = $attachment['Document']['file'];
			}

			$this->SysEmail->set($this->data);
			
			// send it!
			if ($this->SysEmail->validates()) {
				$e = 0;
				
				$toUsers = explode(',', $this->data['SysEmail']['to']);
				$toUsers = array_unique($toUsers);
				
				if (in_array($this->data['SysEmail']['email_users'], array('both', 'household_contact'))) {
					$households = $User->HouseholdMember->Household->getHouseholdIds($toUsers);
					$contacts = $User->HouseholdMember->Household->find('all', array(
						'fields' => array(
							'contact_id'
						),
						'conditions' => array(
							'id' => $households
						)
					));
					$extraUsers = Set::extract('/Household/contact_id', $contacts);
					if ($this->data['SysEmail']['email_users'] == 'both') {
						$toUsers = array_merge($toUsers, $extraUsers);
					} else {
						$toUsers = $extraUsers;
					}
				}
				
				$toUsers = array_unique($toUsers);
				$this->set('allToUsers', $toUsers);

				foreach ($toUsers as $toUser) {
					if ($this->Notifier->notify(array(
						'from' => $fromUser,
						'to' => $toUser,
						'subject' => $this->data['SysEmail']['subject'],
						'attachments' => $attachments,
						'body' => $this->data['SysEmail']['body']
					), 'email')) {
						$e++;
					}
				}

				$this->Session->setFlash('Your emails have been sent.', 'flash'.DS.'success');
				
				// delete attachments related with this email
				$this->SysEmail->gcAttachments($this->MultiSelect->_token);
			} else {
				$this->Session->setFlash('Unable to send your emails.', 'flash'.DS.'failure');
			}			
		} else {
			// clear old attachments that people aren't using anymore
			$this->SysEmail->gcAttachments();
		}

		$User->contain(array(
			'Profile' => array(
				'fields' => array(
					'id','name','primary_email'
				)
			)
		));
		$this->set('toUsers', $User->find('all', array('conditions'=>array('User.id'=>$toUsers))));
		$User->contain(array(
			'Profile' => array(
				'fields' => array(
					'id','name','primary_email'
				)
			)
		));
		$this->set('fromUser', $User->read(null, $fromUser));
		$this->set('showAttachments', true);
	}
}	
?>