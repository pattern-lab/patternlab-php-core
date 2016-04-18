<?php

/*!
 * Config Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Configures Pattern Lab by checking config files and required files
 *
 */

namespace PatternLab;

use \PatternLab\Console;
use \PatternLab\FileUtil;
use \PatternLab\Timer;
use \Symfony\Component\Yaml\Yaml;
use \Symfony\Component\Yaml\Exception\ParseException;

class Config {
	
	protected static $options            = array();
	protected static $userConfig         = "config.yml";
	protected static $userConfigDir      = "";
	protected static $userConfigDirClean = "config";
	protected static $userConfigDirDash  = "_config";
	protected static $userConfigPath     = "";
	protected static $plConfigPath       = "config/config.yml.default";
	protected static $dirAdded           = false;
	
	/**
	* Clean a given dir from the config file
	* @param  {String}       directory to be cleaned
	*
	* @return {String}       cleaned directory
	*/
	protected static function cleanDir($dir) {
		
		$dir = trim($dir);
		$dir = ($dir[0] == DIRECTORY_SEPARATOR) ? ltrim($dir, DIRECTORY_SEPARATOR) : $dir;
		$dir = ($dir[strlen($dir)-1] == DIRECTORY_SEPARATOR) ? rtrim($dir, DIRECTORY_SEPARATOR) : $dir;
		
		return $dir;
		
	}
	
	/**
	* Get the value associated with an option from the Config
	* @param  {String}       the name of the option to be checked
	* 
	* @return {String/Boolean} the value of the get or false if it wasn't found
	*/
	public static function getOption($optionName = "") {
		
		if (empty($optionName)) {
			return false;
		}
		
		if (array_key_exists($optionName,self::$options)) {
			return self::$options[$optionName];
		}
		
		return false;
		
	}
	
	/**
	* Get the options set in the config
	* 
	* @return {Array}        the options from the config
	*/
	public static function getOptions() {
		return self::$options;
	}
	
	/**
	* Adds the config options to a var to be accessed from the rest of the system
	* If it's an old config or no config exists this will update and generate it.
	* @param  {Boolean}       whether we should print out the status of the config being loaded
	*/
	public static function init($baseDir = "", $verbose = true) {
		
		// make sure a base dir was supplied
		if (empty($baseDir)) {
			Console::writeError("need a base directory to initialize the config class...");
		}
		
		// normalize the baseDir
		$baseDir = FileUtil::normalizePath($baseDir);
		
		// double-check the default config file exists
		if (!is_dir($baseDir)) {
			Console::writeError("make sure ".$baseDir." exists...");
		}
		
		// set the baseDir option
		self::$options["baseDir"] = ($baseDir[strlen($baseDir)-1] == DIRECTORY_SEPARATOR) ? $baseDir : $baseDir.DIRECTORY_SEPARATOR;
		
		// can't add __DIR__ above so adding here
		if (!self::$dirAdded) {
			
			// set-up the paths
			self::$userConfigDirClean  = self::$options["baseDir"].self::$userConfigDirClean;
			self::$userConfigDirDash   = self::$options["baseDir"].self::$userConfigDirDash;
			self::$userConfigDir       = (is_dir(self::$userConfigDirDash)) ? self::$userConfigDirDash : self::$userConfigDirClean;
			self::$userConfigPath      = self::$userConfigDir.DIRECTORY_SEPARATOR.self::$userConfig;
			self::$plConfigPath        = self::$options["baseDir"]."vendor/pattern-lab/core/".self::$plConfigPath;
			self::$dirAdded            = true;
			
			// just in case the config directory doesn't exist at all
			if (!is_dir(self::$userConfigDir)) {
				mkdir(self::$userConfigDir);
			}
			
		}
		
		// make sure migrate doesn't happen by default
		$migrate     = false;
		$diffVersion = false;
		
		// double-check the default config file exists
		if (!file_exists(self::$plConfigPath)) {
			Console::writeError("make sure <path>".self::$plConfigPath."</path> exists before trying to have Pattern Lab build the config.yml file automagically...");
		}
		
		// set the default config using the pattern lab config
		try {
			$data = Yaml::parse(file_get_contents(self::$plConfigPath));
		} catch (ParseException $e) {
			Console::writeError("Config parse error in <path>".self::$plConfigPath."</path>: ".$e->getMessage());
		}
		
		// load the options from the default file
		self::loadOptions($data);
		
		// make sure these are copied
		$defaultOptions = self::$options;
		
		// check to see if the user config exists, if not create it
		if ($verbose) {
			Console::writeLine("configuring pattern lab...");
		}
		
		if (!file_exists(self::$userConfigPath)) {
			$migrate = true;
		} else {
			try {
				$data = Yaml::parse(file_get_contents(self::$userConfigPath));
			} catch (ParseException $e) {
				Console::writeError("Config parse error in <path>".self::$userConfigPath."</path>: ".$e->getMessage());
			}
			self::loadOptions($data);
		}
		
		// compare version numbers
		$diffVersion = (self::$options["v"] != $defaultOptions["v"]) ? true : false;
		
		// run an upgrade and migrations if necessary
		if ($migrate || $diffVersion) {
			if ($verbose) {
				Console::writeInfo("upgrading your version of pattern lab...");
			}
			if ($migrate) {
				if (!@copy(self::$plConfigPath, self::$userConfigPath)) {
					Console::writeError("make sure that Pattern Lab can write a new config to ".self::$userConfigPath."...");
					exit;
				}
			} else {
				self::$options = self::writeNewConfigFile(self::$options,$defaultOptions);
			}
		}
		
		// making sure the config isn't empty
		if (empty(self::$options) && $verbose) {
			Console::writeError("a set of configuration options is required to use Pattern Lab...");
			exit;
		}
		
		// set-up the various dirs
		self::$options["coreDir"]          = is_dir(self::$options["baseDir"]."_core") ? self::$options["baseDir"]."_core" : self::$options["baseDir"]."core";
		self::$options["exportDir"]        = isset(self::$options["exportDir"])   ? self::$options["baseDir"].self::cleanDir(self::$options["exportDir"])   : self::$options["baseDir"]."exports";
		self::$options["packagesDir"]      = isset(self::$options["packagesDir"]) ? self::$options["baseDir"].self::cleanDir(self::$options["packagesDir"]) : self::$options["baseDir"]."packages";
		self::$options["publicDir"]        = isset(self::$options["publicDir"])   ? self::$options["baseDir"].self::cleanDir(self::$options["publicDir"])   : self::$options["baseDir"]."public";
		self::$options["scriptsDir"]       = isset(self::$options["scriptsDir"])  ? self::$options["baseDir"].self::cleanDir(self::$options["scriptsDir"])  : self::$options["baseDir"]."scripts";
		self::$options["sourceDir"]        = isset(self::$options["sourceDir"])   ? self::$options["baseDir"].self::cleanDir(self::$options["sourceDir"])   : self::$options["baseDir"]."source";
		self::$options["componentDir"]     = self::$options["publicDir"]."/patternlab-components";
		self::$options["dataDir"]          = self::$options["sourceDir"]."/_data";
		self::$options["patternExportDir"] = self::$options["exportDir"]."/patterns";
		self::$options["patternPublicDir"] = self::$options["publicDir"]."/patterns";
		self::$options["patternSourceDir"] = self::$options["sourceDir"]."/_patterns";
		
		// make sure styleguideExcludes is set to an array even if it's empty
		if (is_string(self::$options["styleGuideExcludes"])) {
			self::$options["styleGuideExcludes"] = array();
		}
		
		// set the cacheBuster
		self::$options["cacheBuster"] = (self::$options["cacheBusterOn"] == "false") ? 0 : time();
		
		// provide the default for enable CSS. performance hog so it should be run infrequently
		self::$options["enableCSS"] = false;
		
		// which of these should be exposed in the front-end?
		self::$options["exposedOptions"] = array();
		self::setExposedOption("cacheBuster");
		self::setExposedOption("ishFontSize");
		self::setExposedOption("ishMaximum");
		self::setExposedOption("ishMinimum");
		self::setExposedOption("patternExtension");
		
	}
	
	/**
	* Check to see if the given array is an associative array
	* @param  {Array}        the array to be checked
	* 
	* @return {Boolean}      whether it's an associative array
	*/
	protected static function isAssoc($array) {
		return (bool) count(array_filter(array_keys($array), 'is_string'));
	}
	
	/**
	* Load the options into self::$options
	* @param  {Array}        the data to be added
	* @param  {String}       any addition that may need to be added to the option key
	*/
	public static function loadOptions($data,$parentKey = "") {
		
		foreach ($data as $key => $value) {
			
			$key = $parentKey.trim($key);
			
			if (is_array($value) && self::isAssoc($value)) {
				self::loadOptions($value,$key.".");
			} else if (is_array($value) && !self::isAssoc($value)) {
				self::$options[$key] = $value;
			} else {
				self::$options[$key] = trim($value);
			}
			
		}
		
	}
	
	/**
	* Add an option and associated value to the base Config
	* @param  {String}       the name of the option to be added
	* @param  {String}       the value of the option to be added
	* 
	* @return {Boolean}      whether the set was successful
	*/
	public static function setOption($optionName = "", $optionValue = "") {
		
		if (empty($optionName) || empty($optionValue)) {
			return false;
		}
		
		if (!array_key_exists($optionName,self::$options)) {
			self::$options[$optionName] = $optionValue;
			return true;
		}
		
		return false;
		
	}
	
	/**
	* Add an option to the exposedOptions array so it can be exposed on the front-end
	* @param  {String}       the name of the option to be added to the exposedOption arrays
	* 
	* @return {Boolean}      whether the set was successful
	*/
	public static function setExposedOption($optionName = "") {
		
		if (!empty($optionName) && isset(self::$options[$optionName])) {
			if (!in_array($optionName,self::$options["exposedOptions"])) {
				self::$options["exposedOptions"][] = $optionName;
			}
			return true;
		}
		
		return false;
		
	}
	
	/**
	* Update a single config option based on a change in composer.json
	* @param  {String}       the name of the option to be changed
	* @param  {String}       the new value of the option to be changed
	*/
	public static function updateConfigOption($optionName,$optionValue) {
		
		if (is_string($optionValue) && strpos($optionValue,"<prompt>") !== false) {
			
			// prompt for input using the supplied query
			$prompt  = str_replace("</prompt>","",str_replace("<prompt>","",$optionValue));
			$options = "";
			$input   = Console::promptInput($prompt,$options,false);
			
			self::writeUpdateConfigOption($optionName,$input);
			Console::writeTag("ok","config option ".$optionName." updated...", false, true);
			
		} else if (!isset(self::$options[$optionName]) || (self::$options["overrideConfig"] == "a")) {
			
			// if the option isn't set or the config is always to override update the config
			self::writeUpdateConfigOption($optionName,$optionValue);
			
		} else if (self::$options["overrideConfig"] == "q") {
			
			// standardize the values for comparison
			$currentOptionValue = is_array(self::$options[$optionName]) ? implode(", ",self::$options[$optionName]) : self::$options[$optionName];
			$newOptionValue     = is_array($optionValue) ? implode(", ",$optionValue) : $optionValue;
			
			if ($currentOptionValue != $newOptionValue) {
				
				// prompt for input
				$prompt  = "update the config option <desc>".$optionName." (".$currentOptionValue.")</desc> with the value <desc>".$newOptionValue."</desc>?";
				$options = "Y/n";
				$input   = Console::promptInput($prompt,$options);
				
				if ($input == "y") {
					self::writeUpdateConfigOption($optionName,$optionValue);
					Console::writeInfo("config option ".$optionName." updated...", false, true);
				} else {
					Console::writeWarning("config option <desc>".$optionName."</desc> not  updated...", false, true);
				}
				
			}
			
		}
		
	}
	
	/**
	* Update an option and associated value to the base Config
	* @param  {String}       the name of the option to be updated
	* @param  {String}       the value of the option to be updated
	* 
	* @return {Boolean}      whether the update was successful
	*/
	public static function updateOption($optionName = "", $optionValue = "") {
		
		if (empty($optionName) || empty($optionValue)) {
			return false;
		}
		
		if (array_key_exists($optionName,self::$options)) {
			if (is_array(self::$options[$optionName])) {
				$optionValue = is_array($optionValue) ? $optionValue : array($optionValue);
				self::$options[$optionName] = array_merge(self::$options[$optionName], $optionValue);
			} else {
				self::$options[$optionName] = $optionValue;
			}
			return true;
		}
		
		return false;
		
	}
	
	/**
	* Write out the new config option value
	* @param  {String}       the name of the option to be changed
	* @param  {String}       the new value of the option to be changed
	*/
	protected static function writeUpdateConfigOption($optionName,$optionValue) {
		
		// parse the YAML options
		try {
			$options = Yaml::parse(file_get_contents(self::$userConfigPath));
		} catch (ParseException $e) {
			Console::writeError("Config parse error in <path>".self::$userConfigPath."</path>: ".$e->getMessage());
		}
		
		if (isset($options[$optionName]) && is_array($options[$optionName])) {
			$optionValue = is_array($optionValue) ? $optionValue : array($optionValue);
			$options[$optionName] = array_merge($options[$optionName], $optionValue);
		} else {
			$options[$optionName] = $optionValue;
		}
		
		// dump the YAML
		$configOutput = Yaml::dump($options, 3);
		
		// write out the new config file
		file_put_contents(self::$userConfigPath,$configOutput);
		
	}
	
	/**
	* Use the default config as a base and update it with old config options. Write out a new user config.
	* @param  {Array}        the old configuration file options
	* @param  {Array}        the default configuration file options
	*
	* @return {Array}        the new configuration
	*/
	protected static function writeNewConfigFile($oldOptions,$defaultOptions) {
		
		// iterate over the old config and replace values in the new config
		foreach ($oldOptions as $key => $value) {
			if ($key != "v") {
				$defaultOptions[$key] = $value;
			}
		}
		
		// dump the YAML
		$configOutput = Yaml::dump($defaultOptions, 3);
		
		// write out the new config file
		file_put_contents(self::$userConfigPath,$configOutput);
		
		return $defaultOptions;
		
	}
	
}
