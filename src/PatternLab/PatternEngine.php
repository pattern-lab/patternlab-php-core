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

class PatternEngine {
	
	public static $patternLoader;
	public static $rules          = array();
	
	/**
	* Load a new instance of the Pattern Loader
	*/
	public static function init($options) {
		
		$found = false;
		self::loadRules($options);
		
		foreach (self::$rules as $rule) {
			if ($rule->test()) {
				$found = true;
				self::$patternLoader = $rule->getInstance($options);
			}
		}
		
		if (!$found) {
			print "the supplied pattern extension didn't match a pattern loader rule. please check.\n";
			exit;
		}
		
	}
	
	/**
	* Load all of the rules related to Pattern Engine
	*/
	public static function loadRules($options) {
		
		foreach (glob(__DIR__."/PatternEngine/Rules/*.php") as $filename) {
			$rule = str_replace(".php","",str_replace(__DIR__."/PatternEngine/Rules/","",$filename));
			if ($rule[0] != "_") {
				$ruleClass     = "\PatternLab\PatternEngine\Rules\\".$rule;
				self::$rules[] = new $ruleClass($options);
			}
		}
		
		// fine pattern engines that might be in plugins
		$pluginDir = str_replace("src/PatternLab/../../","",\PatternLab\Config::$options["pluginDir"]);
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pluginDir), \RecursiveIteratorIterator::CHILD_FIRST);
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		foreach($objects as $name => $object) {
			if ((strpos($name,"PatternEngineRule.php") !== false) && (strpos($name,"plugins/vendor/") === false)) {
				$dirs               = explode("/",$object->getPath());
				$patternEngineName  = "\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$object->getFilename());
				self::$rules[]      = new $patternEngineName($options);
			}
		}
		
	}
	
}
