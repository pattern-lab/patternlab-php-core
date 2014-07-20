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

class Config {
	
	public    static $options            = array();
	protected static $userConfig         = "config.ini";
	protected static $userConfigDir      = "";
	protected static $userConfigDirClean = "config";
	protected static $userConfigDirDash  = "_config";
	protected static $userConfigPath     = "";
	protected static $plConfigPath       = "../../config/config.ini.default";
	protected static $cleanValues        = array("ie","id","patternStates","styleGuideExcludes");
	protected static $dirAdded           = false;
	
	/**
	* Adds the config options to a var to be accessed from the rest of the system
	* If it's an old config or no config exists this will update and generate it.
	* @param  {Boolean}       whether we should print out the status of the config being loaded
	*/
	public static function init($baseDir = "", $verbose = true) {
		
		// make sure a base dir was supplied
		if (empty($baseDir)) {
			Console::writeLine("<error>need a base directory to initialize the config class...</error>"); exit;
		}
		
		// normalize the baseDir
		$baseDir = FileUtil::normalizePath($baseDir);
		self::$options["baseDir"] = $baseDir;
		
		// can't add __DIR__ above so adding here
		if (!self::$dirAdded) {
			
			// set-up the paths
			self::$userConfigDirClean  = $baseDir.DIRECTORY_SEPARATOR.self::$userConfigDirClean;
			self::$userConfigDirDash   = $baseDir.DIRECTORY_SEPARATOR.self::$userConfigDirDash;
			self::$userConfigDir       = (is_dir(self::$userConfigDirDash)) ? self::$userConfigDirDash : self::$userConfigDirClean;
			self::$userConfigPath      = self::$userConfigDir.DIRECTORY_SEPARATOR.self::$userConfig;
			self::$plConfigPath        = __DIR__.DIRECTORY_SEPARATOR.self::$plConfigPath;
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
			Console::writeLine("<error>make sure config.ini.default exists before trying to have Pattern Lab build the config.ini file automagically...</error>"); exit;
		}
		
		// set the default config using the pattern lab config
		$defaultOptions = self::$options = parse_ini_file(self::$plConfigPath);
		
		// check to see if the user config exists, if not create it
		if ($verbose) {
			Console::writeLine("configuring pattern lab...");
		}
		
		if (!file_exists(self::$userConfigPath)) {
			$migrate = true;
		} else {
			self::$options = parse_ini_file(self::$userConfigPath);
		}
		
		// compare version numbers
		$diffVersion = (self::$options["v"] != $defaultOptions["v"]) ? true : false;
		
		// run an upgrade and migrations if necessary
		if ($migrate || $diffVersion) {
			if ($verbose) {
				Console::writeLine("<info>upgrading your version of pattern lab...</info>");
			}
			if ($migrate) {
				if (!@copy(self::$plConfigPath, self::$userConfigPath)) {
					Console::writeLine("<error>make sure that Pattern Lab can write a new config to ".self::$userConfigPath."...</error>");
					exit;
				}
			} else {
				self::$options = self::writeNewConfigFile(self::$options,$defaultOptions);
			}
		}
		
		// making sure the config isn't empty
		if (empty(self::$options) && $verbose) {
			Console::writeLine("<error>a set of configuration options is required to use Pattern Lab...");
			exit;
		}
		
		// set-up the various dirs
		self::$options["baseDir"]          = $baseDir;
		self::$options["exportDir"]        = $baseDir.DIRECTORY_SEPARATOR.self::cleanDir(self::$options["exportDir"]);
		self::$options["packagesDir"]      = $baseDir.DIRECTORY_SEPARATOR.self::cleanDir(self::$options["packagesDir"]);
		self::$options["publicDir"]        = $baseDir.DIRECTORY_SEPARATOR.self::cleanDir(self::$options["publicDir"]);
		self::$options["sourceDir"]        = $baseDir.DIRECTORY_SEPARATOR.self::cleanDir(self::$options["sourceDir"]);
		self::$options["patternExportDir"] = self::$options["exportDir"]."/patterns";
		self::$options["patternPublicDir"] = self::$options["publicDir"]."/patterns";
		self::$options["patternSourceDir"] = self::$options["sourceDir"]."/_patterns";
		
		// populate some standard variables out of the config
		foreach (self::$options as $key => $value) {
			
			// if the variables are array-like make sure the properties are validated/trimmed/lowercased before saving
			if (in_array($key,self::$cleanValues)) {
				$values = explode(",",$value);
				array_walk($values,'PatternLab\Util::trim');
				self::$options[$key] = $values;
			} else if ($key == "ishControlsHide") {
				self::$options[$key] = new \stdClass();
				$class = self::$options[$key];
				if ($value != "") {
					$values = explode(",",$value);
					foreach($values as $value2) {
						$value2 = trim($value2);
						$class->$value2 = true;
					}
				}
			}
			
		}
		
		// set the cacheBuster
		self::$options["cacheBuster"] = (self::$options["cacheBusterOn"] == "false") ? 0 : time();
		
		// provide the default for enable CSS. performance hog so it should be run infrequently
		self::$options["enableCSS"] = false;
		
	}
	
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
	* Update a single config option
	* @param  {String}       the name of the option to be changed
	* @param  {String}       the new value of the option to be changed
	*/
	public static function updateConfigOption($optionName,$optionValue) {
		
		// check if we should notify the user of a change
		if (isset(Config::$options[$optionName])) {
			$stdin = fopen("php://stdin", "r");
			Console::writeLine("<info>update the config option '".$optionName."' with the value '".$optionValue."'? Y/n > </info><nophpeol>");
			$answer = strtolower(trim(fgets($stdin)));
			fclose($stdin);
			if ($answer == "y") {
				self::writeUpdateConfigOption($optionName,$optionValue);
				Console::writeLine("<ok>config option '".$optionName."' updated...</ok>", false, true);
			} else {
				Console::writeLine("<warning>config option '".$optionName."' not  updated...</warning>", false, true);
			}
		} else {
			self::writeUpdateConfigOption($optionName,$optionValue);
		}
		
	}
	
	/**
	* Write out the new config option value
	* @param  {String}       the name of the option to be changed
	* @param  {String}       the new value of the option to be changed
	*/
	protected static function writeUpdateConfigOption($optionName,$optionValue) {
		
		$configOutput = "";
		$options      = parse_ini_file(self::$userConfigPath);
		$options[$optionName] = $optionValue;
		
		foreach ($options as $key => $value) {
			$configOutput .= $key." = \"".$value."\"\n";
		}
		
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
		
		// create the output data
		$configOutput = "";
		foreach ($defaultOptions as $key => $value) {
			$configOutput .= $key." = \"".$value."\"\n";
		}
		
		// write out the new config file
		file_put_contents(self::$userConfigPath,$configOutput);
		
		return $defaultOptions;
		
	}
	
}
