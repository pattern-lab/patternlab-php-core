<?php

/*!
 * Installer Util Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Various functions to be run before and during composer package installs
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Timer;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use \Symfony\Component\Finder\Finder;

class InstallerUtil {
	
	protected static $fs;
	
	/**
	 * Move the component files from the package to their location in the patternlab-components dir
	 * @param  {String/Array}   the items to create a fileList for
	 *
	 * @return {Array}          list of files destination and source
	 */
	protected static function buildFileList($initialList) {
		
		$fileList = array();
		
		// see if it's an array. loop over the multiple items if it is
		if (is_array($initialList)) {
			foreach ($initialList as $listItem) {
				$fileList[$listItem] = $listItem;
			}
		} else {
			$fileList[$listItem] = $listItem;
		}
		
		return $fileList;
		
	}
	
	/**
	* Common init sequence
	*/
	protected static function init() {
		
		// initialize the console to print out any issues
		Console::init();
		
		// initialize the config for the pluginDir
		$baseDir = __DIR__."/../../../../../";
		Config::init($baseDir,false);
		
		// load the file system function
		self::$fs = new Filesystem();
	}
	
	/**
	 * Parse the component types to figure out what needs to be moved and added to the component JSON files
	 * @param  {String}    file path to move
	 * @param  {String}    file path to move to
	 * @param  {String}    the name of the package
	 * @param  {String}    the base directory for the source of the files
	 * @param  {String}    the base directory for the destination of the files (publicDir or sourceDir)
	 * @param  {Array}     the list of files to be moved
	 */
	protected static function moveFiles($source,$destination,$packageName,$sourceBase,$destinationBase) {
		
		// make sure the destination base exists
		if (!is_dir($destinationBase)) {
			mkdir($destinationBase);
		}
		
		// clean any * or / on the end of $destination
		$destination = ($destination[strlen($destination)-1] == "*") ? substr($destination,0,-1) : $destination;
		$destination = ($destination[strlen($destination)-1] == "/") ? substr($destination,0,-1) : $destination;
		
		// decide how to move the files. the rules:
		// dest ~ src        -> action
		// *    ~ *          -> mirror to path/
		// path ~ *          -> mirror to path/
		// path ~ foo/*      -> mirror to path/foo
		// path ~ foo/s.html -> copy tp path/foo/s.html
		
		if (($source == "*") && ($destination == "*")) {
			if (!self::pathExists($packageName,$destinationBase."/")) {
				self::$fs->mirror($sourceBase,$destinationBase."/");
			}
		} else if ($source == "*") {
			if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
				self::$fs->mirror($sourceBase,$destinationBase."/".$destination);
			}
		} else if ($source[strlen($source)-1] == "*") {
			$source = rtrim($source,"/*");
			if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
				self::$fs->mirror($sourceBase.$source,$destinationBase."/".$destination);
			}
		} else {
			$pathInfo       = explode("/",$destination);
			$file           = array_pop($pathInfo);
			$destinationDir = implode("/",$pathInfo);
			if (!self::$fs->exists($destinationBase."/".$destinationDir)) {
				self::$fs->mkdir($destinationBase."/".$destinationDir);
			}
			if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
				self::$fs->copy($sourceBase.$source,$destinationBase."/".$destination,true);
			}
		}
		
	}
	
	/**
	 * Parse the component types to figure out what needs to be moved and added to the component JSON files
	 * @param  {String}    the name of the package
	 * @param  {String}    the base directory for the source of the files
	 * @param  {String}    the base directory for the destination of the files (publicDir or sourceDir)
	 * @param  {Array}     the list of files to be moved
	 */
	protected static function parseComponentTypes($packageName,$sourceBase,$destinationBase,$componentTypes) {
		
		/*
		NEED TO KNOW TYPES BEFORE MOVING, they just get mirrored
		"dist": {
			"componentDir": { // patternlab-components/package/name/
				"css": "css/*", // string, object, or array
				"javascript": { // string, object, or array
					"files": // string or array
					"onready": // string
				}
				"images": // string, object or array
				"templates": // string, object or array
			}
		*/
		
		/*
		iterate over a source or source dirs and copy files into the componentdir. 
		use file extensions to add them to the appropriate arrays below. so...
			"patternlab": {
				"dist": {
					"componentDir": {
						{ "*": "*" }
					}
				}
				"onready": ""
			}
			
		}
		
		/*
		patternlab-components/templates.json (read in via PHP and written out as data.json), loaded via AJAX
			for PHP: { "templates": [...] }
			for JS:  var templates = [ { "pattern-lab/plugin-kss": [ "dist/templates/foo.mustache "] } ];
		patternlab-components/javascript.json (read in via PHP and written out as data.json), $script uses the data.json to load the list of files
			for PHP: { "javascript": [...] };
			for JS:  var javascript = [ { "pattern-lab/plugin-kss": { "dependencies": [ "path.js" ], "onready": "code" } } ];
		patternlab-components/css.json (read in via PHP and written out as data.json), simple loader uses data.json to the load the list of files
			for PHP: { "css": [...] };
			for JS:  var css = [ { "pattern-lab/plugin-kss": [ "path1.css", "path2.css" ] } ];
		*/
		
		$destinationBase = $destinationBase."/".$packageName;
		
		// check if css option is set
		if (isset($componentTypes["css"])) {
			
			$fileList = self::buildFileList($componentTypes["css"]);
			self::parseFileList($packageName,$sourceBase,$destinationBase,$fileList);
			self::updateComponentJSON("css",$componentTypes["css"]);
			
		}
		
		// check if the javascript option is set
		if (isset($componentList["javascript"])) {
			
			// check to see if this has options
			if (is_array($componentList["javascript"]) && (isset($componentTypes["javascript"]["files"]))) {
				$fileList       = self::buildFileList($componentList["javascript"]["files"]);
				$javascriptList = $componentList["javascript"]["files"];
			} else {
				$fileList       = self::buildFileList($componentList["javascript"]);
				$javascriptList = $componentList["javascript"];
			}
			
			self::parseFileList($packageName,$sourceBase,$destinationBase,$fileList);
			self::updateComponentJSON("javascript",$javascriptList);
			
		}
		
		// check if the images option is set
		if (isset($componentTypes["images"])) {
			
			$fileList = self::buildFileList($componentTypes["images"]);
			self::parseFileList($packageName,$sourceBase,$destinationBase,$fileList);
			
		}
		
		// check if the templates option is set
		if (isset($componentList["templates"])) {
			
			$fileList = self::buildFileList($componentTypes["templates"]);
			self::parseFileList($packageName,$sourceBase,$destinationBase,$fileList);
			self::updateComponentJSON("templates",$componentTypes["templates"]);
			
		}
		
	}
	
	/**
	 * Move the files from the package to their location in the public dir or source dir
	 * @param  {String}    the name of the package
	 * @param  {String}    the base directory for the source of the files
	 * @param  {String}    the base directory for the destintation of the files (publicDir or sourceDir)
	 * @param  {Array}     the list of files to be moved
	 */
	protected static function parseFileList($packageName,$sourceBase,$destinationBase,$fileList) {
		
		foreach ($fileList as $fileItem) {
			
			// retrieve the source & destination
			$destination = self::removeDots(key($fileItem));
			$source      = self::removeDots($fileItem[$destination]);
			
			// depending on the source handle things differently. mirror if it ends in /*
			if (is_array($source)) {
				foreach ($source as $key => $value) {
					self::moveFiles($value,$key,$packageName,$sourceBase,$destinationBase);
				}
			} else {
				self::moveFiles($source,$destination,$packageName,$sourceBase,$destinationBase);
			}
			
			
		}
		
	}
	
	/**
	 * Check to see if the path already exists. If it does prompt the user to double-check it should be overwritten
	 * @param  {String}    the package name
	 * @param  {String}    path to be checked
	 *
	 * @return {Boolean}   if the path exists and should be overwritten
	 */
	protected static function pathExists($packageName,$path) {
		
		if (self::$fs->exists($path)) {
			
			// set-up a human readable prompt
			$humanReadablePath = str_replace(Config::getOption("baseDir"), "./", $path);
			
			// set if the prompt should fire
			$prompt = true;
			
			// are we checking a directory?
			if (is_dir($path)) {
				
				// see if the directory is essentially empty
				$files = scandir($path);
				foreach ($files as $key => $file) {
					$ignore = array("..",".",".gitkeep","README",".DS_Store");
					$file = explode("/",$file);
					if (in_array($file[count($file)-1],$ignore)) {
						unset($files[$key]);
					}
				}
				
				if (empty($files)) {
					$prompt = false;
				}
				
			}
			
			if ($prompt) {
				
				// prompt for input using the supplied query
				$prompt  = "the path <path>".$humanReadablePath."</path> already exists. overwrite it with the contents from the <path>".$packageName."</path> package?";
				$options = "Y/n";
				$input   = Console::promptInput($prompt,$options);
				
				if ($input == "y") {
					Console::writeTag("ok","contents of <path>".$humanReadablePath."</path> being overwritten...", false, true);
					return false;
				} else {
					Console::writeWarning("contents of <path>".$humanReadablePath."</path> weren't overwritten. some parts of the <path>".$packageName."</path> package may be missing...", false, true);
					return true;
				}
				
			}
			
			return false;
			
		}
		
		return false;
		
	}
	
	/**
	 * Run the PL tasks when a package is installed
	 * @param  {Object}     a script event object from composer
	 */
	public static function postPackageInstall($event) {
		
		// run the console and config inits
		self::init();
		
		// run the tasks based on what's in the extra dir
		self::runTasks($event,"install");
		
	}
	
	/**
	 * Run the PL tasks when a package is updated
	 * @param  {Object}     a script event object from composer
	 */
	public static function postPackageUpdate($event) {
		
		// run the console and config inits
		self::init();
		
		self::runTasks($event,"update");
		
	}
	
	/**
	 * Make sure certain things are set-up before running composer's install
	 * @param  {Object}     a script event object from composer
	 */
	public static function preInstallCmd($event) {
		
		// run the console and config inits
		self::init();
		
		// default vars
		$sourceDir   = Config::getOption("sourceDir");
		$packagesDir = Config::getOption("packagesDir");
		
		// check directories
		if (!is_dir($sourceDir)) {
			mkdir($sourceDir);
		}
		
		if (!is_dir($packagesDir)) {
			mkdir($packagesDir);
		}
		
	}
	
	/**
	 * Make sure pattern engines and listeners are removed on uninstall
	 * @param  {Object}     a script event object from composer
	 */
	public static function prePackageUninstallCmd($event) {
		
		// run the console and config inits
		self::init();
		
		// get package info
		$package   = $event->getOperation()->getPackage();
		$type      = $package->getType();
		$name      = $package->getName();
		$pathBase  = Config::getOption("packagesDir")."/".$name;
		
		// see if the package has a listener and remove it
		self::scanForListener($pathBase,true);
		
		// see if the package is a pattern engine and remove the rule
		if ($type == "patternlab-patternengine") {
			self::scanForPatternEngineRule($pathBase,true);
		}
		
	}
	
	/**
	 * Remove dots from the path to make sure there is no file system traversal when looking for or writing files
	 * @param  {String}    the path to check and remove dots
	 *
	 * @return {String}    the path minus dots
	 */
	protected static function removeDots($path) {
		$parts = array();
		foreach (explode("/", $path) as $chunk) {
			if ((".." !== $chunk) && ("." !== $chunk) && ("" !== $chunk)) {
				$parts[] = $chunk;
			}
		}
		return implode("/", $parts);
	}
	
	/**
	 * Handle some Pattern Lab specific tasks based on what's found in the package's composer.json file
	 * @param  {Object}     a script event object from composer
	 * @param  {String}     the type of event starting the runTasks command
	 */
	protected static function runTasks($event,$type) {
		
		// get package info
		$package   = ($type == "install") ? $event->getOperation()->getPackage() : $event->getOperation()->getTargetPackage();
		$extra     = $package->getExtra();
		$type      = $package->getType();
		$name      = $package->getName();
		$pathBase  = Config::getOption("packagesDir")."/".$name;
		$pathDist  = $pathBase."/dist/";
		
		// make sure we're only evaluating pattern lab packages
		if (strpos($type,"patternlab-") !== false) {
			
			// make sure that it has the name-spaced section of data to be parsed
			if (isset($extra["patternlab"])) {
				
				// rebase $extra
				$extra = $extra["patternlab"];
				
				// move assets to the base directory
				if (isset($extra["dist"]["baseDir"])) {
					self::parseFileList($name,$pathDist,Config::getOption("baseDir"),$extra["dist"]["baseDir"]);
				}
				
				// move assets to the public directory
				if (isset($extra["dist"]["publicDir"])) {
					self::parseFileList($name,$pathDist,Config::getOption("publicDir"),$extra["dist"]["publicDir"]);
				}
				
				// move assets to the source directory
				if (isset($extra["dist"]["sourceDir"])) {
					self::parseFileList($name,$pathDist,Config::getOption("sourceDir"),$extra["dist"]["sourceDir"]);
				}
				
				// move assets to the scripts directory
				if (isset($extra["dist"]["scriptsDir"])) {
					self::parseFileList($name,$pathDist,Config::getOption("scriptsDir"),$extra["dist"]["scriptsDir"]);
				}
				
				// move assets to the data directory
				if (isset($extra["dist"]["dataDir"])) {
					self::parseFileList($name,$pathDist,Config::getOption("dataDir"),$extra["dist"]["dataDir"]);
				}
				
				// move assets to the components directory
				if (isset($extra["dist"]["componentDir"])) {
					self::parseComponentTypes($name,$pathDist,Config::getOption("componentDir")."/".$name,$extra["dist"]["componentDir"]);
				}
				
				// see if we need to modify the config
				if (isset($extra["config"])) {
					
					foreach ($extra["config"] as $optionInfo) {
						
						// get config info
						$option = key($optionInfo);
						$value  = $optionInfo[$option];
						
						// update the config option
						Config::updateConfigOption($option,$value);
						
					}
					
				}
				
			}
			
			// see if the package has a listener
			self::scanForListener($pathBase);
			
			// see if the package is a pattern engine
			if ($type == "patternlab-patternengine") {
				self::scanForPatternEngineRule($pathBase);
			}
			
		}
		
	}
	
	/**
	 * Scan the package for a listener
	 * @param  {String}     the path for the package
	 */
	protected static function scanForListener($pathPackage,$remove = false) {
		
		// get listener list path
		$pathList = Config::getOption("packagesDir")."/listeners.json";
		
		// make sure listeners.json exists. if not create it
		if (!file_exists($pathList)) {
			file_put_contents($pathList, "{ \"listeners\": [ ] }");
		}
		
		// load listener list
		$listenerList = json_decode(file_get_contents($pathList),true);
		
		// set-up a finder to find the listener
		$finder = new Finder();
		$finder->files()->name('PatternLabListener.php')->in($pathPackage);
		
		// iterate over the returned objects
		foreach ($finder as $file) {
			
			// create the name
			$dirs         = explode("/",$file->getPath());
			$listenerName = "\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$file->getFilename());
			
			// check to see what we should do with the listener info
			if (!$remove && !in_array($listenerName,$listenerList["listeners"])) {
				$listenerList["listeners"][] = $listenerName;
			} else if ($remove && in_array($listenerName,$listenerList["listeners"])) {
				$key = array_search($listenerName, $listenerList["listeners"]);
				unset($listenerList["listeners"][$key]);
			}
			
			// write out the listener list
			file_put_contents($pathList,json_encode($listenerList));
			
		}
		
	}
	
	/**
	 * Scan the package for a pattern engine rule
	 * @param  {String}     the path for the package
	 */
	protected static function scanForPatternEngineRule($pathPackage,$remove = false) {
		
		// get listener list path
		$pathList = Config::getOption("packagesDir")."/patternengines.json";
		
		// make sure patternengines.json exists. if not create it
		if (!file_exists($pathList)) {
			file_put_contents($pathList, "{ \"patternengines\": [ ] }");
		}
		
		// load pattern engine list
		$patternEngineList = json_decode(file_get_contents($pathList),true);
		
		// set-up a finder to find the pattern engine
		$finder = new Finder();
		$finder->files()->name("PatternEngineRule.php")->in($pathPackage);
		
		// iterate over the returned objects
		foreach ($finder as $file) {
			
			/// create the name
			$dirs              = explode("/",$file->getPath());
			$patternEngineName = "\\".$dirs[count($dirs)-3]."\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$file->getFilename());
			
			// check what we should do with the pattern engine info
			if (!$remove && !in_array($patternEngineName, $patternEngineList["patternengines"])) {
				$patternEngineList["patternengines"][] = $patternEngineName;
			} else if ($remove && in_array($patternEngineName, $patternEngineList["patternengines"])) {
				$key = array_search($patternEngineName, $patternEngineList["patternengines"]);
				unset($patternEngineList["patternengines"][$key]);
			}
			
			// write out the pattern engine list
			file_put_contents($pathList,json_encode($patternEngineList));
			
		}
		
	}
	
}
