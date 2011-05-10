<?php
/**
 * Short description for file.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

 /**
  * Extensions to redirect views/layouts
  */
	Router::parseExtensions('json', 'csv', 'print');
 
/**
 * Bring in custom routing libraries
 */
	App::import('Lib', array('Slugger.routes/SluggableRoute'));
 
/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/views/pages/home.ctp)...
 */
	Router::connect('/', array('controller' => 'users', 'action' => 'login'));
	
/**
 * Custom routes
 */
	Router::connectNamed(array('User', 'Ministry', 'Involvement', 'Campus', 'model'), array('defaults' => true));
	Router::connect('/:controller/:action/*',
	   array(),
		array(
			'routeClass' => 'SluggableRoute',
			'models' => array('User', 'Ministry', 'Involvement', 'Campus')
		)
	);
	Router::connect('/pages/phrase/*', array('controller' => 'pages', 'action' => 'phrase'));

/*
 * Asset Compress
 */
	Router::connect('/css_cache/*', array('plugin' => 'asset_compress', 'controller' => 'css_files', 'action' => 'get'));
	Router::connect('/js_cache/*', array('plugin' => 'asset_compress', 'controller' => 'js_files', 'action' => 'get'));
 
/**
 * ...and connect the rest of 'Pages' controller's urls.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
?>