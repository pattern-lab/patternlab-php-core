<?php

/*!
 * Pattern Engine Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Set-up the selected pattern engine
 *
 */

namespace PatternLab;

use \PatternLab\Config;

class PatternEngine {
	
	public static $rules = array();
	public static $instance;
	
	/**
	* Load a new instance of the Pattern Loader
	*/
	public static function init() {
		
		$found = false;
		self::loadRules();
		
		foreach (self::$rules as $rule) {
			if ($rule->test()) {
				self::$instance = $rule;
				$found = true;
				break;
			}
		}
		
		if (!$found) {
			print "the supplied pattern extension didn't match a pattern loader rule. please check...\n";
			exit;
		}
		
	}
	
	/**
	* Load all of the rules related to Pattern Engines. They're located in the plugin dir
	*/
	public static function loadRules() {
		
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Config::$options["pluginDir"]), \RecursiveIteratorIterator::CHILD_FIRST);
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		foreach($objects as $name => $object) {
			if (strpos($name,"PatternEngineRule.php") !== false) {
				$dirs              = explode("/",$object->getPath());
				$patternEngineName = "\\".$dirs[count($dirs)-3]."\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$object->getFilename());
				self::$rules[]     = new $patternEngineName();
			}
		}
		
	}
	
}
