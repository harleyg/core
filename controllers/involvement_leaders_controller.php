<?php

App::import('Controller', 'Leaders');

class InvolvementLeadersController extends LeadersController {

	var $model = 'Involvement';

/**
 * Model::beforeFilter() callback
 *
 * Sets permissions for this controller.
 *
 * @access private
 */ 
	function beforeFilter() {
		parent::beforeFilter();
		$this->modelId = isset($this->passedArgs[$this->model]) ? $this->passedArgs[$this->model] : null;
	}
	
}

?>