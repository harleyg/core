<?php
/* Comment Fixture generated on: 2010-06-28 09:06:53 : 1277741153 */
class CommentFixture extends CakeTestFixture {
	var $name = 'Comment';

	var $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'user_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'comment_type_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'comment' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'created_by' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => 1,
			'user_id' => 1,
			'comment_type_id' => 3,
			'comment' => 'another comment',
			'created_by' => NULL,
			'created' => '2010-03-24 09:53:55',
			'modified' => '2010-03-24 09:53:55'
		),
		array(
			'id' => 2,
			'user_id' => 1,
			'comment_type_id' => 1,
			'comment' => 'comment\'d!',
			'created_by' => NULL,
			'created' => '2010-03-24 10:04:59',
			'modified' => '2010-03-24 10:04:59'
		),
		array(
			'id' => 3,
			'user_id' => 1,
			'comment_type_id' => 1,
			'comment' => 'test',
			'created_by' => NULL,
			'created' => '2010-04-08 07:46:26',
			'modified' => '2010-04-08 07:46:26'
		)
	);
}
?>