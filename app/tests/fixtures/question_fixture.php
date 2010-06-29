<?php
/* Question Fixture generated on: 2010-06-28 09:06:19 : 1277741419 */
class QuestionFixture extends CakeTestFixture {
	var $name = 'Question';

	var $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
		'involvement_id' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 10),
		'order' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 2),
		'description' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => 1,
			'involvement_id' => 1,
			'order' => 1,
			'description' => 'What is the color blue?',
			'created' => '2010-04-13 09:33:14',
			'modified' => '2010-04-13 09:33:14'
		),
		array(
			'id' => 2,
			'involvement_id' => 1,
			'order' => 2,
			'description' => 'another question!',
			'created' => '2010-04-09 13:05:40',
			'modified' => '2010-04-09 13:16:14'
		)
	);
}
?>