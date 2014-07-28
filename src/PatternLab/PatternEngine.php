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
use \PatternLab\Console;
use \PatternLab\Timer;

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
			Console::writeLine("<error>the supplied pattern extension didn't match a pattern loader rule...</error>");
			exit;
		}
		
	}
	
	/**
	* Load all of the rules related to Pattern Engines. They're located in the plugin dir
	*/
	public static function loadRules() {
		
		// default var
		$packagesDir = Config::getOption("packagesDir");
		
		// see if the package dir exists. if it doesn't it means composer hasn't been run
		if (!is_dir($packagesDir)) {
			Console::writeLine("<error>you haven't fully set-up Pattern Lab yet. please add a pattern engine...</error>");
			exit;
		}
		
		// make sure the pattern engine data exists
		if (file_exists($packagesDir."/patternengines.json")) {
			
			// get pattern engine list data
			$patternEngineList = json_decode(file_get_contents($packagesDir."/patternengines.json"), true);
			
			// get the pattern engine info
			foreach ($patternEngineList["patternengines"] as $patternEngineName) {
				
				self::$rules[] = new $patternEngineName();
				
			}
			
		}
		
	}
	
}
