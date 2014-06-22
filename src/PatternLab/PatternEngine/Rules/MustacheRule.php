<?php

/*!
 * Pattern Engine Mustache Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If the test matches "mustache" it will return an instance of the Mustache Pattern Engine
 *
 */


namespace PatternLab\PatternEngine\Rules;

use \PatternLab\Config;
use \PatternLab\PatternEngine\Loaders\MustacheLoader;

class MustacheRule extends \PatternLab\PatternEngine\Rule {
	
	protected $helpers = array();
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->engineProp = "mustache";
		
	}
		
	public function getInstance($options) {
		
		$this->loadHelpers();
		
		$options["loader"]         = new MustacheLoader(__DIR__."/../../".Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		$options["partial_loader"] = new MustacheLoader(__DIR__."/../../".Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		$options["helpers"]        = $this->helpers;
		
		return new \Mustache_Engine($options);
		
	}
	
	/**
	* Load helpers to add tags to Mustache
	*/
	protected function loadHelpers() {
		
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Config::$options["pluginDir"]), \RecursiveIteratorIterator::CHILD_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			if ((strpos($name,"MustacheHelper.php") !== false) && (strpos($name,"plugins/vendor/") === false)) {
				$dirs            = explode("/",$object->getPath());
				$helperName      = "\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$object->getFilename());
				$helper          = new $helperName();
				foreach ($helper->helpers as $tag => $function) {
					$this->helpers[$tag] = $function;
				}
			}
			
		}
		
	}
	
}
