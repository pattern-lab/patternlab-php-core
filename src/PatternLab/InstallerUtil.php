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

class InstallerUtil {
	
	/**
	* Common init sequence
	*/
	protected static function init() {
		
		// initialize the console to print out any issues
		Console::init();
		
		// initialize the config for the pluginDir
		$baseDir = __DIR__."/../../../../../";
		Config::init($baseDir,false);
		
	}
	
	/**
	 * Move the files from the package to their location in the public dir or source dir
	 * @param  {String}    the name of the package
	 * @param  {String}    the base directory for the source of the files
	 * @param  {String}    the base directory for the destintation of the files (publicDir or sourceDir)
	 * @param  {Array}     the list of files to be moved
	 */
	protected static function parseFileList($packageName,$sourceBase,$destinationBase,$fileList) {
		
		$fs = new Filesystem();
		
		foreach ($fileList as $fileItem) {
			
			// retrieve the source & destination
			$source      = self::removeDots(key($fileItem));
			$destination = self::removeDots($fileItem[$source]);
			
			// make sure the destination base exists
			if (!is_dir($destinationBase)) {
				mkdir($destinationBase);
			}
			
			// depending on the source handle things differently. mirror if it ends in /*
			if (($source == "*") && ($destination == "*")) {
				if (!self::pathExists($packageName,$destinationBase."/")) {
					$fs->mirror($sourceBase,$destinationBase."/");
				}
			} else if (($source == "*") && ($destination[strlen($source)-1] == "*")) {
				$destination = rtrim($destination,"/*");
				if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
					$fs->mirror($sourceBase,$destinationBase."/".$destination);
				}
			} else if ($source[strlen($source)-1] == "*") {
				$source      = rtrim($source,"/*");
				$destination = rtrim($destination,"/*");
				if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
					$fs->mirror($sourceBase.$source,$destinationBase."/".$destination);
				}
			} else {
				$pathInfo       = explode("/",$destination);
				$file           = array_pop($pathInfo);
				$destinationDir = implode("/",$pathInfo);
				if (!$fs->exists($destinationBase."/".$destinationDir)) {
					$fs->mkdir($destinationBase."/".$destinationDir);
				}
				if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
					$fs->copy($sourceBase.$source,$destinationBase."/".$destination,true);
				}
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
		
		$fs = new Filesystem();
		
		if ($fs->exists($path)) {
			
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
				$prompt  = "the path <path>".$path."</path> already exists. overwrite it with the contents of <path>".$packageName."</path>?";
				$options = "Y/n";
				$input   = Console::promptInput($prompt,$options);
				
				if ($input == "y") {
					Console::writeTag("ok","contents of <path>".$path."</path> being overwritten...", false, false);
					return false;
				} else {
					Console::writeWarning("contents of <path>".$path."</path> weren't overwritten. some parts of the <path>".$packageName."</path> package may be missing...", false, false);
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
		
		self::runTasks($event,"install");
		
	}
	
	/**
	 * Run the PL tasks when a package is updated
	 * @param  {Object}     a script event object from composer
	 */
	public static function postPackageUpdate($event) {
		
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
		
		// run the console and config inits
		self::init();
		
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
					self::parseFileList($name,$pathDist,Config::getOption("dataDir"),$extra["dist"]["scriptsDir"]);
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
		
		// grab the list of files in the package
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathPackage), \RecursiveIteratorIterator::CHILD_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		// go through the package items
		foreach($objects as $name => $object) {
			
			if ($object->getFilename() == "PatternLabListener.php") {
				
				// create the name
				$dirs         = explode("/",$object->getPath());
				$listenerName = "\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$object->getFilename());
				
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
		
		// grab the list of files in the package
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pathPackage), \RecursiveIteratorIterator::CHILD_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		// go through the package items
		foreach ($objects as $name => $object) {
			
			if ($object->getFilename() == "PatternEngineRule.php") {
				
				// create the name
				$dirs              = explode("/",$object->getPath());
				$patternEngineName = "\\".$dirs[count($dirs)-3]."\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$object->getFilename());
				
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
	
}
