<?php
/**
 * Roster controller class.
 *
 * @copyright     Copyright 2010, *ROCK*HARBOR
 * @link          http://rockharbor.org *ROCK*HARBOR
 * @package       core
 * @subpackage    core.app.controllers
 */

/**
 * Rosters Controller
 *
 * @package       core
 * @subpackage    core.app.controllers
 */
class RostersController extends AppController {

/**
 * The name of the controller
 *
 * @var string
 */
	var $name = 'Rosters';

/**
 * Extra components for this controller
 *
 * @var array
 */
	var $components = array(
		'FilterPagination' => array(
			'startEmpty' => false
		),
		'AuthorizeDotNet',
		'MultiSelect.MultiSelect');

/**
 * Extra helpers for this controller
 *
 * @var array
 */
	var $helpers = array('Formatting', 'MultiSelect.MultiSelect');
	
/**
 * Model::beforeFilter() callback
 *
 * Used to override Acl permissions for this controller.
 *
 * @access private
 */ 
	function beforeFilter() {
		/*$this->Security->blackHoleCallback = '_forceSSL';
		$this->Security->requireSecure('add');*/
		parent::beforeFilter();
		$this->_editSelf('involvement', 'add');
	}
	
/**
 * Displays a roster list
 *
 * ### Passed args:
 * - integer $Involvement The id of the involvement to filter for
 * - integer $User The id of the user to filter for
 *
 * @todo place user list limit into involvement()
 */ 
	function index() {
		$conditions = array();
		$userConditions = array();
		$involvementId = $this->passedArgs['Involvement'];
		
		// if involvement is defined, show just that involvement
		$conditions['Roster.involvement_id'] = $involvementId;
		
		// get roster ids
		$roster = $this->Roster->find('all', compact('conditions'));
		$rosterIds = Set::extract('/Roster/user_id', $roster);

		// if we're limiting this to one user, just pull their household signup data
		$householdIds = array();
		if (isset($this->passedArgs['User'])) {
			$householdIds = $this->Roster->User->HouseholdMember->Household->getMemberIds($this->passedArgs['User'], true);
			$viewableIds = array_intersect($householdIds, $rosterIds);
			$viewableIds[] = $this->passedArgs['User'];
			$conditions['User.id'] = $viewableIds;
		}

		if (!empty($this->data)) {
			if (!empty($this->data['Filter']['roster_status_id'])) {
				$conditions['Roster.roster_status_id'] = $this->data['Filter']['roster_status_id'];
			}
			$conditions += $this->Roster->parseCriteria(array('roles' => $this->data['Filter']['Role']));
		} else {
			$this->data = array(
				'Filter' => array(
					'pending' => 0
				)
			);
		}
		
		$link = array(
			'User' => array(
				'Profile' => array(
					'fields' => array(
						'name',
						'cell_phone',
						'allow_sponsorage'
					)
				),
				'Image'
			),
			'RosterStatus'
		);
		$contain = array('Role');
		
		$this->Roster->recursive = 0;
		$this->paginate = compact('conditions','link','contain');
		
		// save search for multi select actions
		$this->MultiSelect->saveSearch($this->paginate);
		
		// set based on criteria
		$this->set('canCheckAll', !isset($this->passedArgs['User']));
		$this->Roster->Involvement->contain(array('InvolvementType', 'Leader'));
		$involvement = $this->Roster->Involvement->read(null, $involvementId);

		$childConditions = $countConditions = $pendingConditions = array('Roster.involvement_id' => $involvementId);
		$childConditions['Roster.parent_id >'] = 0;
		$pendingConditions['Roster.roster_status_id'] = 2;
		$counts['childcare'] = $this->Roster->find('count', array('conditions' => $childConditions));
		$counts['pending'] = $this->Roster->find('count', array('conditions' => $pendingConditions));
		$counts['leaders'] = count($involvement['Leader']);
		$counts['total'] = $this->Roster->find('count', array('conditions' => $countConditions));

		$roles = $this->Roster->Involvement->Ministry->Role->find('list', array(
			'conditions' => array(
				'Role.ministry_id' => $involvement['Involvement']['ministry_id']
			)
		));
		$rosterStatuses = $this->Roster->RosterStatus->find('list');

		$this->set('rosters', $this->FilterPagination->paginate());
		$this->set(compact('involvement', 'rosterIds', 'householdIds', 'rosterStatuses', 'counts', 'roles'));
	}

/**
 * Shows involvement history
 *
 * ### Passed args:
 * - integer $User The id of the user
 */ 	
	function involvement() {
		$userId = $this->passedArgs['User'];
		
		if (!$userId) {
			//404
			$this->Session->setFlash(__('Invalid user', true));
			$this->redirect($this->referer());
		}

		$leaderOf = $this->Roster->Involvement->Leader->find('list', array(
			'fields' => array(
				'Leader.id',
				'Leader.model_id'				
			),
			'conditions' => array(
				'Leader.model' => 'Involvement',
				'Leader.user_id' => $userId
			)
		));

		$memberOf = $this->Roster->find('list', array(
			'fields' => array(
				'Roster.id',
				'Roster.involvement_id'
			),
			'conditions' => array(
				'Roster.user_id' => $userId
			)
		));

		$conditions = array(
			'Involvement.id' => array_values($memberOf)	
		);
		$private = array_key_exists($this->activeUser['Group']['id'], $this->Roster->User->Group->findGroups(Core::read('general.private_group'), 'list', '>'));

		if ($this->Session->check('FilterPagination.data') && empty($this->data)) {
			$this->data = $this->Session->read('FilterPagination.data');
		}
		$_default = array(
			'Roster' => array(
				'passed' => 0,
				'leading' => 1,
				'inactive' => 0,
				'private' => $private
			)
		);
		$this->data = $search = Set::merge($_default, $this->data);
		
		if ($this->data['Roster']['leading']) {
			$conditions['Involvement.id'] = array_merge(array_values($leaderOf), array_values($memberOf));
		}
		if ($this->data['Roster']['passed'] == false) {
			$db = $this->Roster->getDataSource();
			$conditions[] = $db->expression('('.$this->Roster->Involvement->getVirtualField('passed').') = '.$this->data['Roster']['passed']);
		}
		if (!$this->data['Roster']['inactive']) {
			$conditions['Involvement.active'] = true;
		}
		if (!$this->data['Roster']['private']) {
			$conditions['Involvement.private'] = false;
		}

		$this->paginate = array(
			'fields' => array(
				'id', 'name', 'passed', 'active', 'private'
			),
			'conditions' => $conditions,
			'contain' => array(					
				'Date',
				'InvolvementType',
				'Roster' => array(
					'conditions' => array(
						'Roster.user_id' => $userId
					),
					'Role'
				)
			)
		);
		$rosters = $this->FilterPagination->paginate('Involvement');
		foreach ($rosters as &$roster) {
			$roster['Involvement']['dates'] = $this->Roster->Involvement->Date->generateDates($roster['Involvement']['id'], array('limit' => 1));
		}

		$this->set(compact('userId', 'leaderOf', 'rosters', 'private', 'memberOf'));
	}

/**
 * Signs a user up for an involvement opportunity
 *
 * Checks payment information, if needed. Creates childcare records, Roster records,
 * Payment records and runs credit cards.
 *
 * ### Passed args:
 * - integer `User` The (main) user id to sign up
 * - integer `Involvement` The involvement opportunity
 */
	function add() {
		$userId = $this->passedArgs['User'];
		$involvementId = $this->passedArgs['Involvement'];
		
		if (!$userId || !$involvementId) {
			//404
			$this->Session->setFlash('Invalid id', 'flash'.DS.'failure');
			$this->redirect(array('action'=>'index'));
		}

		// get needed information about the user and this involvement
		$this->Roster->Involvement->contain(array('InvolvementType', 'Question'));
		$involvement = $this->Roster->Involvement->read(null, $involvementId);

		// can't sign up for inactive or past involvements
		if (!$involvement['Involvement']['active'] && !$involvement['Involvement']['passed']) {
			$this->Session->setFlash('Cannot sign up for an inactive or past event.', 'flash'.DS.'failure');
			$this->redirect($this->emptyPage);
		}
		
		// create model to make use of validation
		$CreditCard = ClassRegistry::init('CreditCard');
		// get roster ids for comparison (to see if they're signed up)
		$involvementRoster = $this->Roster->find('list', array(
			'conditions' => array(
				'Roster.id',
				'Roster.involvement_id' => $involvementId
			),
			'fields' => array(
				'Roster.id',
				'Roster.user_id'
			),
			'contain' => false
		));
		///HouseholdMember/Household/HouseholdMember/User/Profile
		$this->Roster->User->contain(array(
			'Profile',
			'HouseholdMember' => array(
				'Household' => array(
					'HouseholdMember' => array(
						'User' => array(
							'Profile'
						)
					)
				)
			)
		));
		$user = $this->Roster->User->read(null, $userId);

		$members = $this->Roster->User->HouseholdMember->Household->getMemberIds($userId, true);
		
		// they're submitting the form
		if (!empty($this->data)) {
			// first thing we'll do is validate all the data. if it all validates, we'll try to
			// process the credit card. if the credit card goes through, we'll add everyone to the 
			// roster (including childcare) and save the payment info
						
			// extract info to check/save for roster
			$rValidates = true;
			$this->Roster->_validationErrors = array();
			
			foreach ($this->data['Adult'] as $roster => &$values) {
				if ($values['Roster']['user_id'] == 0) {
					unset($this->data['Adult'][$roster]);
					continue;
				}
				$values = $this->Roster->setDefaultData(array(
					'roster' => $values,
					'involvement' => $involvement,
					'defaults' => $this->data['Default'],
					'creditCard' => $this->data,
					'payer' => $this->activeUser
				));

				// save validate success only if we haven't failed yet (so not to overwrite a failure)
				if ($rValidates) {
					$rValidates = $this->Roster->saveAll($values, array('validate' => 'only'));
				} else {
					// still validate this roster to generate errors
					$this->Roster->saveAll($values, array('validate' => 'only'));
				}

				// save validation errors
				if (!empty($this->Roster->validationErrors)) {
					$this->Roster->_validationErrors[$roster] = $this->Roster->validationErrors;
				}
			}
			
			// find the signed up parent for this child. by default, it's this user. then it's household contact.
			$pValidates = true;
			// get signed up users
			$possibleParents = Set::extract('/Adult/Roster/user_id', $this->data);
			// get household contacts found that are signed up
			$contacts = array_intersect(Set::extract('/HouseholdMember/Household/contact_id'), $possibleParents);
			if (in_array($user['User']['id'], $possibleParents)) {
				$parent = $user['User']['id'];
			} elseif (count($contacts) > 0) {
				$parent = $contacts[0];
			} elseif (count($possibleParents) > 0) {
				$parent = $possibleParents[0];
			} else {
				$pValidates = false;
			}			
			
			// extract info to check/save for childcare
			$cValidates = true;
			if (isset($this->data['Child']) && $pValidates) {
				foreach ($this->data['Child'] as $roster => &$child) {
					$child = $this->Roster->setDefaultData(array(
						'roster' => $child,
						'involvement' => $involvement,
						'defaults' => $this->data['Default'],
						'creditCard' => $this->data,
						'payer' => $this->activeUser,
						'parent' => $parent
					));

					// save validate success only if we haven't failed yet (so not to overwrite a failure)
					if ($cValidates) {
						$cValidates = $this->Roster->saveAll($child, array('validate' => 'only'));
					} else {
						// still validate this roster to generate errors
						$this->Roster->saveAll($child, array('validate' => 'only'));
					}
				}
			} else {
				$cValidates = true;
			}

			// check to make sure this doesn't exceed the roster limit
			$lValidates = true;
			$currentCount = $this->Roster->find('count', array(
				'conditions' => array(
					'Roster.involvement_id' => $involvement['Involvement']['id']
				),
				'contain' => false
			));
			$rosterCount = count($this->data['Adult']);
			$childCount = isset($this->data['Child']) ? count($this->data['Child']) : 0;
			if (!empty($involvement['Involvement']['roster_limit'])) {
				$lValidates = $rosterCount + $childCount + $currentCount <= $involvement['Involvement']['roster_limit'];
			} else {
				$lValidates = true;
			}

			$this->set('involvement', $involvement);

			// combine roster validation errors
			$this->Roster->validationErrors = $this->Roster->_validationErrors;
			$Adult = new Model(array(
				'table' => false,
				'name' => 'Adult'
			));
			$Adult->validationErrors = $this->Roster->_validationErrors;
			// check all validation before continuing with save
			if ($lValidates && $rValidates && $cValidates && $pValidates) {
				// Now that we know that the data will save, let's run the credit card
				// get all signed up users (for their name)
				if ($involvement['Involvement']['take_payment'] && $this->data['Default']['payment_option_id'] > 0 && !$this->data['Default']['pay_later']) {
					$signedUpIds = array_merge(Set::extract('/Adult/Roster/user_id', $this->data), Set::extract('/Child/Roster/user_id', $this->data));
					$signedupUsers = $this->Roster->User->Profile->find('all', array(
						'conditions' => array(
							'user_id' => $signedUpIds
						),
						'contain' => false
					));
					$verb = count($signedupUsers) > 1 ? 'have' : 'has';
					$description = implode(' and ', Set::extract('/Profile/name', $signedupUsers)).' '.$verb.' been signed up for '.$involvement['InvolvementType']['name'].' '.$involvement['Involvement']['name'];
					// calculate amount	(use array_values to reset keys)
					$amount = Set::apply('/Payment/amount', array_values($this->data['Adult']), 'array_sum');
					if (isset($this->data['Child'])) {
						$amount += Set::apply('/Payment/amount', array_values($this->data['Child']), 'array_sum');
					}
					
					$paymentOption = $this->Roster->PaymentOption->read(null, $this->data['Default']['payment_option_id']);
					$this->data['CreditCard']['invoice_number'] = $paymentOption['PaymentOption']['account_code'];
					$this->data['CreditCard']['description'] = $description;
					$this->data['CreditCard']['email'] = $user['Profile']['primary_email'];			
					$this->data['CreditCard']['amount'] = $amount;
					
					if ($CreditCard->save($this->data['CreditCard'])) {
						// save main rosters
						foreach ($this->data['Adult'] as $signuproster) {
							$this->Roster->create();
							// include transaction id
							$signuproster['Payment'][0]['transaction_id'] = $CreditCard->transactionId;
							$this->Roster->saveAll($signuproster, array('validate' => false));							
							$this->Notifier->notify(array(
								'to' => $signuproster['Roster']['user_id'],
								'template' => 'involvements_signup',
								'subject' => 'Signed up for '.$involvement['InvolvementType']['name'],
							));
						}
					
						// save childcares
						if (isset($this->data['Child']) && count($this->data['Child'])) {
							foreach ($this->data['Child'] as $signupchild) {
								$this->Roster->create();
								// include transaction id
								$signupchild['Payment'][0]['transaction_id'] = $CreditCard->transactionId;
								$this->Roster->saveAll($signupchild, array('validate' => false));
								$this->Notifier->notify(array(
								'to' => $signupchild['Roster']['user_id'],
								'template' => 'involvements_signup',
								), 'notification');
							}
						}
						
						$this->Notifier->notify(array(
							'to' => $this->activeUser['User']['id'],
							'template' => 'payments_payment_made',
							'subject' => 'Your payment has been made for '.$involvement['InvolvementType']['name'],
						));
						$this->Session->setFlash('Your payment has been received and you have signed up for '.$involvement['Involvement']['name'].'.', 'flash'.DS.'success');
						$this->redirect(array('controller' => 'involvements', 'action' => 'view', 'Involvement' => $involvementId));
					} else {
						$CreditCard->invalidate('credit_card_number', $CreditCard->creditCardError);
						$this->Session->setFlash('Unable to process payment. '.$CreditCard->creditCardError, 'flash'.DS.'failure');
					}
				} else {
					// no credit card, just save as normal
					// save main rosters
					foreach ($this->data['Adult'] as $signuproster) {
						$this->Roster->create();
						$this->Roster->saveAll($signuproster, array('validate' => false));
						$this->Notifier->notify(array(
							'to' => $signuproster['Roster']['user_id'],
							'template' => 'involvements_signup',
							'subject' => 'You have signed up for '.$involvement['InvolvementType']['name'],
						));
					}
					
					// save childcares
					if (isset($this->data['Child']) && count($this->data['Child'])) {
						foreach ($this->data['Child'] as $signupchild) {
							$this->Roster->create();
							$this->Roster->saveAll($signupchild, array('validate' => false));
							$this->Notifier->notify(array(
								'to' => $signupchild['Roster']['user_id'],
								'template' => 'involvements_signup',
							), 'notification');
						}
					}
					
					$this->Session->setFlash('You have signed up for '.$involvement['Involvement']['name'].'.', 'flash'.DS.'success');
					$this->redirect(array('controller' => 'involvements', 'action' => 'view', 'Involvement' => $involvementId));
				}		
			} else {
				// set validation error so modal doesn't close
				if (empty($this->Roster->validationErrors)) {
					$this->Roster->validationErrors = array('validation' => 'failed');
				}

				if (!$pValidates && isset($this->data['Child'])) {
					$this->Session->setFlash('Please assign a parent to this child.', 'flash'.DS.'failure');
				} elseif (!$lValidates) {
					$this->Session->setFlash('Cannot join '.$involvement['Involvement']['name'].'. The roster is full.', 'flash'.DS.'failure');
				} else {
					$this->Session->setFlash('Cannot join '.$involvement['Involvement']['name'].'. Please try again.', 'flash'.DS.'failure');
				}
			}
		}
		
		// get user addresses for js
		$userAddresses = $this->Roster->User->Address->find('all', array(
			'conditions' => array(
				'foreign_key' => $userId,
				'model' => 'User'
			)
		));
		// format for select
		$addresses = Set::combine($userAddresses, '/Address/id', '/Address/name');
		
		// get involvement's payment options for js
		$involvementPaymentOptions = $this->Roster->PaymentOption->find('all', array(
			'conditions' => array(
				'involvement_id' => $involvementId
			)
		));
		// format for select
		$paymentOptions = Set::combine($involvementPaymentOptions, '/PaymentOption/id', '/PaymentOption/name');
		$paymentTypes = $this->Roster->Payment->PaymentType->find('all');
		
		$this->set('roles', $this->Roster->Role->find('list', array(
			'conditions' => array(
				'ministry_id' => $involvement['Involvement']['ministry_id']
			)
		)));
		$this->set(compact('involvement', 'user', 'addresses', 'userAddresses', 'paymentOptions', 'involvementPaymentOptions', 'paymentTypes'));
		$this->set('roster', $involvementRoster);
	}

/**
 * Edits a roster
 *
 * @param integer $id The id of the roster to edit
 * @todo Restrict to proper permissions
 */
	function edit($id = null) {
		if (!$id && empty($this->data)) {
			//404
			$this->Session->setFlash(__('Invalid roster', true));
			$this->redirect(array('action' => 'index'));
		}
		
		// get roster ids for comparison (to see if they're signed up)
		$thisRoster = $this->Roster->read(null, $id);
		
		// get roster ids for comparison (to see if they're signed up)
		$roster = $this->Roster->find('list', array(
			'conditions' => array(
				'Roster.id',
				'Roster.involvement_id' => $thisRoster['Roster']['involvement_id']
			),
			'fields' => array(
				'Roster.id',
				'Roster.user_id'
			),
			'contain' => false
		));
		
		if (!empty($this->data)) {
			if (isset($this->data['Child'])) {
				foreach ($this->data['Child'] as &$child) {
					$child['roster_status_id'] = 1;
					$child['parent_id'] = $this->data['Roster']['user_id'];
					$child['involvement_id'] = $this->data['Roster']['involvement_id'];
					$child['payment_option_id'] = $this->data['Roster']['payment_option_id'];
				}
				
				$children = $this->data['Child'];
				unset($this->data['Child']);
				
				$cValidates = $this->Roster->saveAll($children, array('validate' => 'only'));
			} else {
				$cValidates = true;
			}
			
			$rValidates = $this->Roster->saveAll($this->data, array('validate' => 'only'));
			
			if ($rValidates && $cValidates) {
				$this->Roster->saveAll($this->data, array('validate' => false));
				
				if (isset($children)) {
					$this->Roster->saveAll($children, array('validate' => false));
				}
				
				$this->Session->setFlash('This roster has been saved.', 'flash'.DS.'success');
			} else {
				$this->Session->setFlash('Unable to save this roster. Please try again.', 'flash'.DS.'failure');
			}
			
			if (isset($children)) {
				$this->data['Child'] = $children;
			}
		}
		
		// get needed information about the user and this involvement
		$involvement = $this->Roster->Involvement->read(null, $thisRoster['Roster']['involvement_id']);
		// get user info and all household info where they are the contact
		$householdMemberIds = $this->Roster->User->HouseholdMember->Household->getMemberIds($thisRoster['Roster']['user_id'], true);
		$householdMembers = $this->Roster->User->find('all', array(
			'conditions' => array(
				'User.id' => $householdMemberIds
			),
			'contain' => array(
				'Profile',
				'Group'
			)
		));
		$this->Roster->User->contain(array(
			'Profile',
			'Group'
		));
		$user = $this->Roster->User->read(null, $thisRoster['Roster']['user_id']);
		
		if (empty($this->data)) {
			$this->data = $this->Roster->read(null, $id);
		}
		
		$paymentOptions = $this->Roster->PaymentOption->find('list', array(
			'conditions' => array(
				'involvement_id' => $involvement['Involvement']['id']
			)
		));
		$rosterStatuses = $this->Roster->RosterStatus->find('list');
		
		$this->set(compact('involvement', 'user', 'roster', 'paymentOptions', 'householdMembers', 'rosterStatuses'));
	}

/**
 * Saves roles to a roster id
 *
 * ### Passed Args:
 * - `Involvement` the involvement id
 */
	function roles($roster_id) {
		if (!empty($this->data)) {
			$this->Roster->saveAll($this->data);
			$this->Roster->clearCache();		
		}
		$this->Roster->contain(array(
			'Role'
		));
		$this->Roster->Involvement->contain(array('Ministry' => array('fields' => array('id', 'name'))));
		$ministry = $this->Roster->Involvement->read(null, $this->passedArgs['Involvement']);
		if (empty($this->data) || isset($this->data['Role']['ministry_id'])) {
			$this->data = $this->Roster->read(null, $roster_id);
		}
		$roles = $this->Roster->Role->find('list', array(
			'conditions' => array(
				'Role.ministry_id' => $ministry['Ministry']['id']
			)
		));
		$this->set(compact('roles', 'ministry'));
	}

/**
 * Confirms a set of roster ids
 *
 * @param integer $uid The multi select id
 */
	function confirm($uid = null) {
		$selected = $this->MultiSelect->getSelected($uid);
		$this->Roster->updateAll(
			array('Roster.roster_status_id' => 1),
			array('Roster.id' => $selected)
		);
		$this->Session->setFlash('Roster members confirmed.', 'flash'.DS.'success');
		$this->redirect(array('action'=>'index'));
	}

/**
 * Deletes a set of roster ids
 *
 * @param integer $uid The multi select id
 * @todo Restrict to proper permissions
 */
	function delete($uid = null) {
		$selected = $this->MultiSelect->getSelected($uid);

		if (empty($selected)) {
			//404
			$this->Session->setFlash(__('Roster was not deleted', true));
			$this->redirect(array('action' => 'index'));
		}
		foreach ($selected as $rosterId) {
			$this->Roster->recursive = -1;
			$roster = $this->Roster->read(null, $rosterId);
			// delete any children too
			if ($this->Roster->deleteAll(array(
				'or' => array(
					'Roster.user_id' => $roster['Roster']['user_id'],
					'Roster.parent_id' => $roster['Roster']['user_id']
				),
				'Roster.involvement_id' => $roster['Roster']['involvement_id']
			))) {
				$this->Roster->Involvement->contain(array('InvolvementType'));
				$this->Roster->Involvement->Leader->User->contain(array('Profile'));
				$involvement = $this->Roster->Involvement->read(null, $roster['Roster']['involvement_id']);
				$this->set('involvement', $involvement);
				$user = $this->Roster->Involvement->Leader->User->read(null, $roster['Roster']['user_id']);
				$this->set('user', $user);
				$this->set('activeUser', $this->activeUser);
				// notify the user that they left
				$this->Notifier->notify(array(
					'to' => $roster['Roster']['user_id'],
					'template' => 'rosters_delete',
					'subject' => ($this->activeUser['User']['id'] == $user['User']['id'] ? 'You have' : $user['Profile']['name'].' has').' been removed from '.$involvement['Involvement']['name']
				));
			}
		}
		// notify all the leaders
		$leaders = $this->Roster->Involvement->Leader->find('all', array(
			'conditions' => array(
				'model_id' => $roster['Roster']['involvement_id'],
				'model' => 'Involvement'
			)
		));
		$involvement = $this->Roster->Involvement->read(null, $roster['Roster']['involvement_id']);
		foreach ($leaders as $leader) {
			$user = $this->Roster->Involvement->Leader->User->read(null, $leader['Leader']['user_id']);
			$this->set('user', $user);
			$this->Notifier->notify(array(
				'to' => $leader['Leader']['user_id'],
				'template' => 'rosters_delete',
				'subject' => ($this->activeUser['User']['id'] == $user['User']['id'] ? 'You have' : $user['Profile']['name'].' has').' been removed from '.$involvement['Involvement']['name']
			));
		}
		$this->Session->setFlash('Roster members removed.', 'flash'.DS.'success');
		$this->redirect(array('action'=>'index'));
	}
}
?>