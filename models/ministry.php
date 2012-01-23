<?php
/**
 * Ministry model class.
 *
 * @copyright     Copyright 2010, *ROCK*HARBOR
 * @link          http://rockharbor.org *ROCK*HARBOR
 * @package       core
 * @subpackage    core.app.models
 */

/**
 * Ministry model
 *
 * @package       core
 * @subpackage    core.app.models
 */
class Ministry extends AppModel {

/**
 * The name of the model
 *
 * @var string
 */
	var $name = 'Ministry';
	
/**
 * Default model order
 * 
 * @var string
 */
	var $order = ':ALIAS:.name ASC';

/**
 * Extra behaviors for this model
 *
 * @var array
 */
	var $actsAs = array(
		'Logable',
		'Containable',
		'Tree',
		'Confirm',
		'Cacher.Cache' => array(
			'auto' => false
		),
		'Search.Searchable',
		'Linkable.AdvancedLinkable',
		'NamedScope.NamedScope' => array(
			'active' => array(
				'conditions' => array(
					'active' => true
				)
			)
		)
	);

/**
 * Validation rules
 *
 * @var array
 */
	var $validate = array(
		'name' => array(	
			'rule' => 'notempty',
			'message' => 'Please fill in the required field.'
		),
		'description' => array(	
			'rule' => 'notempty',
			'message' => 'Please fill in the required field.'
		)
	);

/**
 * Sanitization rules
 *
 * @var array
 * @see Sanitizer.SanitizeBehavior
 */
	var $sanitize = array(
		'description' => 'stripScripts'
	);

/**
 * BelongsTo association link
 *
 * @var array
 */
	var $belongsTo = array(
		'ParentMinistry' => array(
			'className' => 'Ministry',
			'foreignKey' => 'parent_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Campus' => array(
			'className' => 'Campus',
			'foreignKey' => 'campus_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
	);

/**
 * HasMany association link
 *
 * @var array
 */
	var $hasMany = array(
		'Involvement' => array(
			'className' => 'Involvement',
			'foreignKey' => 'ministry_id',
			'dependent' => true
		),
		'ChildMinistry' => array(
			'className' => 'Ministry',
			'foreignKey' => 'parent_id',
			'dependent' => true,
			'order' => 'ChildMinistry.name ASC'
		),
		'Role' => array(
			'className' => 'Role',
			'foreignKey' => 'ministry_id',
			'dependent' => true
		),
		'Leader' => array(
			'className' => 'Leader',
			'foreignKey' => 'model_id',
			'dependent' => true,
			'conditions' => array('Leader.model' => 'Ministry')
		),
		'Image' => array(
			'className' => 'Image',
			'foreignKey' => 'foreign_key',
			'dependent' => true,
			'conditions' => array('Image.model' => 'Ministry', 'Image.group' => 'Image', 'Image.approved' => true)
		)
	);

/**
 * HasAndBelongsToMany association link
 *
 * @var array
 */
	var $hasAndBelongsToMany = array(
		'DisplayInvolvement' => array(
			'className' => 'Involvement',
			'table' => 'involvements_ministries',
			'foreignKey' => 'ministry_id',
			'associationForeignKey' => 'involvement_id'
		)
	);

/**
 * Filter args for the Search.Searchable behavior
 *
 * @var array
 * @see Search.Searchable::parseCriteria()
 */
	var $filterArgs = array(
		array(
			'name' => 'simple_fulltext',
			'type' => 'query',
			'method' => 'makeFulltext',
			'field' => array(
				'Ministry.name',
				'Ministry.description',
			)
		),
		array(
			'name' => 'simple',
			'type' => 'query',
			'method' => 'makeLikeConditions',
			'operator' => 'OR',
			'field' => array(
				'Ministry.name',
				'Ministry.description',
			)
		)
	);

/**
 * Array of search filters for SearchesController::simple().
 *
 * They are merged with any existing conditions and parameters sent to
 * Controller::paginate(). Works in conjunction with
 * SearchesController::simple() where arguments sent after the filter name are
 * inserted in order within the filter. Make sure to include contains or links
 * where related model data is needed.
 *
 * @var array
 */	
	var $searchFilter = array(
		'canBePromoted' => array(
			'conditions' => array(
				'Image.approved' => true,
				'Image.promoted' => false
			),
			'link' => array(
				'Image'
			)
		)
	);
	
/**
 * Checks if a user is a manager for a ministry. If they are not, it checks if
 * they manage the parent ministry, if any.
 *
 * @param integer $userId The user id
 * @param integer $ministryId The ministry id
 * @return boolean True if the user is a manager
 * @access public
 */ 
	function isManager($userId = null, $ministryId = null) {
		if (!$userId || !$ministryId) {
			return false;
		}
		
		$managing = $this->Leader->hasAny(array(
			'model' => 'Ministry',
			'model_id' => $ministryId,
			'user_id' => $userId
		));
		if (!$managing) {
			$ministry = $this->read(array('parent_id', 'campus_id'), $ministryId);
			return $this->isManager($userId, $ministry['Ministry']['parent_id']);
		}
		return true;
	}

/**
 * Gets all users involved in all involvements within a ministry
 *
 * @param integer $ministryId The ministry id
 * @param boolean $recursive Whether to pull for subministries as well
 * @return array The user ids
 */
	function getInvolved($ministryId, $recursive = false) {
		if ($recursive) {
			$conditions['or']['Ministry.id'] = $ministryId;
			$conditions['or']['Ministry.parent_id'] = $ministryId;
		} else {
			$conditions['Ministry.id'] = $ministryId;
		}
		$involvements = $this->Involvement->find('all', array(
			'fields' => array(
				'id'
			),
			'conditions' => $conditions,
			'contain' => array(
				'Ministry' => array(
					'fields' => array(
						'id', 'parent_id'
					)
				)
			)
		));
		$options = array(
			'fields' => array(
				'Roster.user_id'
			),
			'conditions' => array(
				'Roster.involvement_id' => Set::extract('/Involvement/id', $involvements)
			),
			'group' => 'Roster.user_id'
		);
		$results = $this->Involvement->Roster->find('all', $options);
		return array_unique(Set::extract('/Roster/user_id', $results));
	}

/**
 * Gets all leaders of all involvements within a ministry
 *
 * @param integer $ministryId The ministry id
 * @param boolean $recursive Whether to pull for subministries as well
 * @return array The user ids
 */
	function getLeaders($ministryId, $recursive = false) {
		$options = array(
			'conditions' => array(
				'Ministry.id' => $ministryId
			),
			'contain' => array(
				'Involvement' => array(
					'fields' => array('id'),
					'Leader' => array(
						'fields' => array('user_id')
					)
				)
			),
			'fields' => array('id')
		);
		if ($recursive) {
			$options['conditions'] = array(
				'or' => array(
					'Ministry.id' => $ministryId,
					'Ministry.parent_id' => $ministryId
				)
			);
		}
		$results = $this->find('all', $options);
		return array_unique(Set::extract('/Involvement/Leader/user_id', $results));
	}
}
?>