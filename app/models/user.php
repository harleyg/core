<?php
/**
 * User model class.
 *
 * @copyright     Copyright 2010, *ROCK*HARBOR
 * @link          http://rockharbor.org *ROCK*HARBOR
 * @package       core
 * @subpackage    core.app.models
 */

/**
 * User model
 *
 * @package       core
 * @subpackage    core.app.models
 */
class User extends AppModel {

/**
 * The name of the model
 *
 * @var string
 */
	var $name = 'User';

/**
 * The field to use when generating lists
 *
 * @var string
 */
	var $displayField = 'username';

/**
 * Extra behaviors for this model
 *
 * @var array
 */
	var $actsAs = array(
		'Logable',	
		'Containable',
		'Merge',
		'Linkable.AdvancedLinkable'
	);

/**
 * Validation rules
 *
 * @var array
 */
	var $validate = array(
		'username' => array(
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'That username is taken.'
			),
			'notempty' => array(
				'rule' => 'notEmpty',
				'message' => 'Gotta have a username.'
			),
			'characters' => array(
				'rule' => '/^[a-z0-9\-_]{5,}$/i',
				'message' => 'Username must be at least 5 characters long and can only contain letters, numbers, dashes and underscores.'
			)
		),
		'password' => array(			
			'minLength' => array(
				'rule' => array('minLength', 6),
				'message' => 'Your password must be at least 6 characters.'
			),
			'alphaNumeric' => array(
				'rule' => 'alphaNumeric',
				'message' => 'Your password must contain only letters and numbers.'
			),
			'notempty' => array(
				'rule' => 'notEmpty',
				'message' => 'Gotta have a password.'
			)
		),
		'confirm_password' => array(
			'identical' => array(
				'rule' => array('identicalFieldValues', 'password'),
				'message' => 'Password confirmation must match password.'
			)
		)
	);

/**
 * HasMany association link
 *
 * @var array
 */
	var $hasMany = array(
		'Comment' => array(
			'className' => 'Comment',
			'foreignKey' => 'user_id',
			'dependent' => true
		),
		'Notification' => array(
			'className' => 'Notification',
			'foreignKey' => 'user_id',
			'dependent' => true
		),
		'Image' => array(
			'className' => 'Image',
			'foreignKey' => 'foreign_key',
			'dependent' => true,
			'conditions' => array('Image.model' => 'User', 'Image.group' => 'Image')
		),
		'Document' => array(
			'className' => 'Document',
			'foreignKey' => 'foreign_key',
			'dependent' => true,
			'conditions' => array('Document.model' => 'User', 'Document.group' => 'Document')
		),
		'Roster' => array(
			'className' => 'Roster',
			'foreignKey' => 'user_id',
			'dependent' => true
		),
		'Address' => array(
			'className' => 'Address',
			'foreignKey' => 'foreign_key',
			'unique' => true,
			'conditions' => array('Address.model' => 'User'),
			'dependent' => true
		),
		'HouseholdMember' => array(
			'dependent' => true
		),
		'Leader' => array(
			'dependent' => true
		),
		'Payment' => array(
			'dependent' => true
		)
	);

/**
 * HasOne association link
 *
 * @var array
 */
	var $hasOne = array(
		'Profile' => array(
			'className' => 'Profile',
			'foreignKey' => 'user_id',
			'dependent' => true
		)
	);

/**
 * BelongsTo association link
 *
 * @var array
 */
	var $belongsTo = array(
		'Group'
	);

/**
 * HasAndBelongsToMany association link
 *
 * @var array
 */
	var $hasAndBelongsToMany = array(
		'Publication' => array(
			'className' => 'Publication',
			'joinTable' => 'publications_users',
			'foreignKey' => 'user_id',
			'associationForeignKey' => 'publication_id',
			'dependent' => true
		)
	);

/**
 * Gets a user id using an arbitrary amount of data by searching a set of
 * distinguishable fields (username, email fields, name, etc.). If more than
 * one match is found it fails.
 * 
 * @param array $data An array of search possibilities. Can be emails, username, names
 * @return mixed False on no matches, the id on a match
 */
	function findUser($data = array()) {
		if (!is_array($data)) {
			$data = array($data);
		}

		$data = Set::filter($data);

		if (empty($data)) {
			return false;
		}		
		
		$foundUser = $this->find('all', array(
			'fields' => 'User.id',
			'conditions' => array(
				'or' => array(
					'and' => array(
						'Profile.first_name' => $data,
						'Profile.last_name' => $data
					),
					'Profile.primary_email' => $data,
					'Profile.alternate_email_1' => $data,
					'Profile.alternate_email_2' => $data,
					'User.username' => $data
				)
			),
			'contain' => array(
				'Profile'
			)
		));
		
		if (count($foundUser) > 1 || empty($foundUser)) {
			return false;
		}
		
		return $foundUser[0]['User']['id'];
	}

/**
 * Creates a User and adds them to a household, or creates a household for them
 *
 * @param array $data Data and related data to save
 * @param integer $householdId The id of the household for them to join. `null` creates a household for them
 * @param array $creator The person creating the user. Empty for self.
 * @return boolean Success
 */
	function createUser($data = array(), $householdId = null, $creator = array()) {
		if (!isset($this->tmpAdded)) {
			$this->tmpAdded = array();
		}
		if (!isset($this->tmpInvited)) {
			$this->tmpInvited = array();
		}

		// add missing data for the main user
		$data = $this->_createUserData($data);
		
		// validate new household members first
		foreach ($data['HouseholdMember'] as $number => &$member) {
			$findConditions = Set::filter($member['Profile']);
			$foundUser = $this->findUser($findConditions);

			if ($foundUser === false) {				
				$member = $this->_createUserData($member);
			} else {
				$this->contain(array('Profile'));
				$found = $this->read(null, $foundUser);
				$member = Set::merge($found, $member);
			}

			// validate
			if ($foundUser === false && empty($member['Profile']['primary_email']) || empty($member['Profile']['first_name']) || empty($member['Profile']['last_name'])) {
				$this->HouseholdMember->invalidate($number.'.Profile.first_name', 'Please fill in all of the information for this user.');
				return false;
			}
		}

		// temporarily remove household member info - we have to do that separately
		$householdMembers = $data['HouseholdMember'];
		unset($data['HouseholdMember']);

		// save user and related info
		$this->create();
		if ($this->saveAll($data)) {
			// needed for creating household members
			$data['User']['id'] = $this->id;

			if (empty($creator)) {
				$this->Profile->saveField('created_by', $this->id);
				$this->Profile->saveField('created_by_type', $data['User']['group_id']);
			}

			// temporarily store userdata for the controller to access and notify them
			$this->tmpAdded[] = array(
				'id' => $data['User']['id'],
				'username' => $data['User']['username'],
				'password' => $data['User']['password']
			);

			if (!$householdId) {
				// create a household for this user and add any members they wanted to add
				$this->HouseholdMember->Household->createHousehold($this->id);
				$householdId = $this->HouseholdMember->Household->id;
				$creator = $data;
			}

			foreach ($householdMembers as $householdMember) {
				if (!isset($householdMember['User']['id'])) {
					$householdMember['Profile']['created_by'] = $creator['User']['id'];
					$householdMember['Profile']['created_by_type'] = $creator['User']['group_id'];

					$this->create();
					if ($this->saveAll($householdMember)) {
						$this->HouseholdMember->Household->join($householdId, $this->id, $creator);
						$this->tmpAdded[] = array(
							'id' => $this->id,
							'username' => $householdMember['User']['username'],
							'password' => $householdMember['User']['password']
						);
					}
				} else {
					$this->HouseholdMember->Household->join($householdId, $householdMember['User']['id'], $creator);
					$this->contain(array('Profile'));
					$oldUser = $this->read(null, $householdMember['User']['id']);
					if ($oldUser['Profile']['child']) {
						$this->tmpAdded[] = array(
							'id' => $oldUser['User']['id'],
							'username' => $oldUser['User']['username'],
							'password' => $oldUser['User']['password']
						);
					} else {
						$this->tmpInvited[] = array(
							'id' => $oldUser['User']['id'],
							'username' => $oldUser['User']['username'],
							'password' => $oldUser['User']['password']
						);
					}
				}
			}
			$this->id = $data['User']['id'];
			return true;
		} else {
			// add household member info back in to fill in fields if it failed
			$data['HouseholdMember'] = $householdMembers;

			return false;
		}
	}

/**
 * Merges current user data with basic needed data. Generates usernames and
 * passwords if empty.
 *
 * @param array $data The partial user data
 * @return array
 */
	function _createUserData($data = array()) {
		$userGroup = $this->Group->findByName('User');

		$default = array(
			'User' => array(
				'username' => null,
				'password' => null,
				'group_id' => $userGroup['Group']['id'],
				'active' => true
			),
			'Address' => array(
					0 => array(
						'primary' => true,
						'active' => true,
						'model' => 'User'
					)
			),
			'Profile' => array(
				'created_by_type' => 0,
				'created_by' => 0
			),
			'HouseholdMember' => array()
		);

		$data = Set::merge($default, $data);

		if (!$data['User']['username']) {
			$data['User']['username'] = $this->generateUsername($data['Profile']['first_name'], $data['Profile']['last_name']);
		}
		if (!$data['User']['password']) {
			$data['User']['password'] = $this->generatePassword();
			$data['User']['confirm_password'] = $data['User']['password'];
			$data['User']['reset_password'] = true;
		}

		return $data;
	}

/**
 * Prepares a search on a user
 *
 * Returns search options unique to this model that will return a list of id's from post conditions
 * that can then be used to search paginate data
 *
 * @param object $Controller The calling controller
 * @param array $data Post data to use for conditions
 * @return array Search option array
 * @access public
 */ 
	function prepareSearch(&$Controller, $data) {
		$_search = array(
			'Search' => array(
				'operator' => 'AND'
			),
			'Profile' => array(
				'Birthday' => array(),
				'email' => array()
			),
			'Distance' => array()
		);
		$data = Set::merge($_search, $data);

		// remove and store fields that aren't actually in the db
		$operator = $data['Search']['operator'];
		unset($data['Search']);
		$dist = $data['Distance'];
		unset($data['Distance']);
		$birthdayRange = $data['Profile']['Birthday'];
		$birthdayRange = array_map('Set::filter', $birthdayRange);
		unset($data['Profile']['Birthday']);
		$email = $data['Profile']['email'];
		unset($data['Profile']['email']);

		// remove blank
		$callback = function($item) use (&$callback) {
			 if (is_array($item)) {
				  return array_filter($item, $callback);
			 }
			 if (!empty($item)) {
				  return $item;
			 }
		};
		$data = array_filter($data, $callback);
		$link = $this->postContains($data);
		
		$conditions = $Controller->postConditions($data, 'LIKE', $operator);
		// prepare for a distance search
		if (!empty($dist['distance_from'])) {
			$coords = $this->Address->geoCoordinates($dist['distance_from']);
			$this->Address->virtualFields = array_merge($this->Address->virtualFields, array(
				'distance' => $this->Address->distance($coords['lat'], $coords['lng'])
			));
			
			// get addresses within distance requirements
			$distancedAddresses = $this->Address->find('all', array(
				'conditions' => array(
					$this->Address->getVirtualField('distance').' <= ' => (int)$dist['distance']
				)
			));
			
			$conditions[$operator]['Address.id'] = array_values(Set::extract('/Address/id', $distancedAddresses));
		}
		
		// prepare age group search
		if (isset($data['Profile']['age'])) {
			$ages = array();
			foreach ($data['Profile']['age'] as $ageGroup) {			
				$ageRange = explode('-', $ageGroup);
				$ages[$operator][] = array($this->Profile->getVirtualField('age').' BETWEEN ? AND ?' => array((int)$ageRange[0], (int)$ageRange[1]));
			}
			$conditions[$operator][] = $ages;
			unset($conditions['Profile.age']);
			unset($conditions[$operator]['Profile.age']);
		}
		
		// check for child
		if (isset($data['Profile']['child'])) {
			$conditions[$operator][$this->Profile->getVirtualField('child')] = $data['Profile']['child'];
			unset($conditions['Profile.child']);
		}
		
		// check for birthday range
		if (!empty($birthdayRange['start']) && !empty($birthdayRange['end'])) {
			krsort($birthdayRange['start']);
			krsort($birthdayRange['end']);
			$start = implode('-', $birthdayRange['start']);
			$end = implode('-', $birthdayRange['end']);
			$conditions['Profile.birth_date BETWEEN ? AND ?'] = array($start, $end);
		}
		
		// check for region
		if (!empty($data['Address']['Zipcode']['region_id'])) {
			$conditions[$operator]['Zipcode.region_id'] = $data['Address']['Zipcode']['region_id'];
			$link['Address']['Zipcode'] = array();
			unset($conditions['Address.Zipcode']);
		}
		
		// check for email 
		if (!empty($email)) {
			$conditions[$operator][] = array(
				'or' => array(
					'Profile.primary_email LIKE' => '%'.$email.'%',
					'Profile.alternate_email_1 LIKE' => '%'.$email.'%',
					'Profile.alternate_email_2 LIKE' => '%'.$email.'%'
				)
			);
			$link['Profile'] = array();
		}

		// check for involvement
		if (!empty($data['Roster']['Involvement']['name'])) {
			$conditions[$operator]['Involvement.name LIKE'] = '%'.$data['Roster']['Involvement']['name'].'%';
			unset($conditions['Roster.Involvement']);
		}
		
		// check for ministry
		if (!empty($data['Roster']['Involvement']['Ministry']['name'])) {
			$conditions[$operator]['Ministry.name LIKE'] = '%'.$data['Roster']['Involvement']['Ministry']['name'].'%';
			//$link['Address']['Zipcode'] = array();
			unset($conditions['Roster.Involvement.Ministry']);
		}
		
		$group = 'User.id';
		
		return compact('link', 'conditions', 'group');
	}

	
/**
 * Generates a username from a name
 *
 * By default, it's $first_name.$last_name (without numbers). If that's taken, it
 * will continue appending numbers until it finds a unique username (up to 8 times).
 *
 * @param string $first_name User's first name
 * @param string $last_name User's last name
 * @return string Generated username
 * @todo Use a while loop instead so it works more than 8 digits
 */
	function generateUsername($first_name = '', $last_name = '') {
		$this->recursive = -1;

		if (empty($first_name) || empty($last_name)) {
			return '';
		}

		$username = strtolower($first_name.$last_name);
		$username = preg_replace('/[^a-z]/', '', $username);

		$user = $this->findByUsername($username);
		if (!empty($user)) {
			for ($x=1; $x <= 8; $x++) {
				$username .= rand(0,9);
				$user = $this->findByUsername($username);
				if (empty($user)) {
					break;
				}
			}
		}
		
		return $username;
	}
	
/**
 * Generates a random password
 *
 * Passwords are generated from a selection of random nouns and verbs,
 * and a 4-digit number is appended to the end. Characters that are 
 * difficult to discern (like '0', 'l', etc.) are replaced. Some other
 * characters are also replaced at random (like 4 for an 'a').
 *
 * @return string Generated password
 * @todo Add more nouns and verbs
 */
	function generatePassword() {
		$nouns = array('jesus', 'core', 'rockharbor', 'php', 'cake', 'pie');
		// 'is' is added in the middle		
		$verbs = array('awesome', 'swell', 'hilarious', 'thebest', 'socool');
		
		$noun = $nouns[array_rand($nouns, 1)];
		$verb = $verbs[array_rand($verbs, 1)];
		
		$rand_noun = '';
		$rand_verb = '';
		
		// shuffle the case around
		for($i = 0; $i < strlen($noun); $i++) {
			$rand_noun .= rand(0,1) ? strtoupper(substr($noun, $i, 1)) : strtolower(substr($noun, $i, 1));
		}
		for($i = 0; $i < strlen($verb); $i++) {
			$rand_verb .= rand(0,1) ? strtoupper(substr($verb, $i, 1)) : strtolower(substr($verb, $i, 1));			
		}
		
		$word = $rand_noun.'is'.$rand_verb;
		$rand_word = '';		
		// replace some letters that may be confusing, or for fun
		for($i = 0; $i < strlen($word); $i++) {
			$char = substr($word, $i, 1);
			if (in_array($char, array('a','A'))) {
				$rand_word .= rand(0,1) ? '4' : $char;
			} else if (in_array($char, array('e','E'))) {
				$rand_word .= rand(0,1) ? '3' : $char;
			} else if ($char == 'I') {
				// I and l's are confusing
				$rand_word .= rand(0,1) ? '1' : 'i';
			} else if ($char == 'l') {
				// I and l's are confusing
				$rand_word .= rand(0,1) ? '7' : 'L';			
			} else if (in_array($char, array('0', 'O'))) {
				// 0 and O's are confusing
				$rand_word .= 'o';
			} else {
				$rand_word .= $char;
			}
		}
		
		// append some numbers
		$num = '';
		for ($i = 0; $i < 4; $i++) {
			$num .= rand(2,9);
		}
		
		return $rand_word.$num;
	}
	
/**
 * Model::beforeSave() callback
 *
 * Used to hash the password field
 *
 * @return boolean True, to save
 * @see Cake docs
 */ 
	function beforeSave() {
		$this->hashPasswords(null, true);
		
		return parent::beforeSave();
	}
	

/**
 * Custom password hashing function for Auth component
 *
 * Hashes if $enforce is true. Prevents hashing before validation so that
 * password and confirm_password validation works and it doesn't require the
 * user to re-enter their password if it doesn't validate. Before the data is
 * saved it is automatically hashed.
 *
 * @param array $data Data passed. Uses User::data if it exists.
 * @param boolean $enforce Force hashing. Used to prevent Auth component from auto-hashing before validation
 * @return array Data with hashed passwords
 * @see User::beforeSave()
 */	
	function hashPasswords($data, $enforce = false) {
		App::import('Component', 'Auth');
		$Auth = new AuthComponent();

		if (!isset($data[$this->alias]['confirm_password'])) {
			// if confirm_password isn't sent, it's probably being sent by auth
			$enforce = true;
		}
		
		if (!empty($this->data)) {
			$data = $this->data;
		}
		
		if ($enforce && isset($data[$this->alias]['password']) && !empty($data[$this->alias]['password']))  {
			$data[$this->alias]['password'] = $Auth->password($data[$this->alias]['password']);
		}
		
		$this->data = $data;
		return $data;
	}
	
/**
 * Encrypts a string (used in old CORE's password authentication)
 * Uses Security.encryptKey in Configure
 *
 * @param string $str String to encrypt
 * @return string Encrypted string
 */	
	function encrypt($str) {
		if (empty($str)) {
			return '';
		}
	   $td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);	    
		mcrypt_generic_init($td, Configure::read('Security.encryptKey'), $iv);
		$encrypted_data = mcrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		    
		return $encrypted_data;
	}
	
/**
 * Decrypts a string (used in old CORE's password authentication)
 * Uses Security.encryptKey in Configure
 *
 * @param string $str String to decrypt
 * @return string Decrypted string
 */	
	function decrypt($str) {
		if (empty($str)) {
			return '';
		}
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		mcrypt_generic_init($td, Configure::read('Security.encryptKey'), $iv);
		$unencrypted_data = mdecrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
					
		return trim($unencrypted_data);
	}
}
?>