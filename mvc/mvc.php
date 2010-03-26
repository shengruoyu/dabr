<?php

session_start();

// Creates a controller, invokes the correct method on the controller, then outputs any controller output
class Dispatcher {
	var $defaultController = 'Default';
	var $defaultAction = 'index';
	
	function dispatch() {		
		$url = stripslashes($_GET['url']);
		$params = explode('/', $url);
		
		// instantiate the controller
		// TODO: work out which controller is actually required
		$contructorClass = "{$this->defaultController}Controller";

		require "app/controllers/{$this->defaultController}.php";
		$controller = new $contructorClass();

		$action = array_shift($params);
		if (!$action) {
			$action = $this->defaultAction;
		}
		
		call_user_func_array(array(&$controller, $action), $params);
		$controller->render($action);
		
		echo $controller->output;
	}
}

class Model { }

// Views generate rendered output, as requested by Controllers, but don't output anything.
class View {
	var $controller = false;
	var $defaultLayout = 'default';
	
	function __construct(&$controller) {
		$this->controller = &$controller;
	}
	
	function render($action = null) {
		// Render the inner page content first.
		$viewFileName = $this->_getViewFileName($action);
		$this->controller->viewVars['pageContent'] = View::_render($viewFileName, $this->controller->viewVars);
		
		// Do layout stuff around the inner content.
		// TODO: provide a way to change layout
		$layoutFileName = $this->_getLayoutFileName($this->defaultLayout);
		$out = View::_render($layoutFileName, $this->controller->viewVars);		
		
		return $out;
	}
	
	function _render($viewFileName, $viewVars) {
		extract($viewVars, EXTR_SKIP);
		ob_start(); 
		include $viewFileName;
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	function _getLayoutFileName($layout) {
		return "app/layouts/{$layout}.php";
	}
	
	function _getViewFileName($action) {
		$controller_name = get_class($this->controller);
		$controller = strtolower(substr($controller_name, 0, strpos($controller_name, 'Controller')));
		return "app/views/{$controller}/{$action}.php";
	}
}

class Controller {
	var $layout = 'default';
	var $output = '';
	var $pageTitle = false;
	var $viewVars = array();
	
	function set($name, $value) {
		$this->viewVars[$name] = $value;
	}
	
	// Instantiates the correct view class, hands it the data, and uses it to render the view output.
	function render($action = null) {
		if ($this->pageTitle == false) {
			$this->pageTitle = ucfirst($action);
		}
		$this->set('pageTitle', $this->pageTitle);
	
		$viewClass = 'View';
		$view =& new $viewClass($this);
		
		// do some header stuff
		// TODO: should this be here?
		$this->preventCaching();
		ob_start('ob_gzhandler');
		header('Content-Type: text/html; charset=utf-8');
		
		$this->output = $view->render($action);
		return $this->output;
	}
	
	function redirect($url) {
		// TODO: allow nicer redirects to internal URLs
		header('Location: ' . $url);
		exit();
	}
	
	function preventCaching() {
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); 
		header('Last-Modified: ' . date('r')); 
		header('Cache-Control: no-store, no-cache, must-revalidate'); 
		header('Cache-Control: post-check=0, pre-check=0', false); 
		header('Pragma: no-cache');
	}
}
