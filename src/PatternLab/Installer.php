<?php

/*!
 * Installer Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Various functions to be run before and during composer package installs
 *
 */

namespace PatternLab;

use \Composer\Script\Event;
use \PatternLab\Config;
use \PatternLab\Console;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Installer {
	
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
			
			// depending on the source handle things differently. mirror if it ends in /*
			if (($source == "*") && ($destination == "*")) {
				if (!self::pathExists($packageName,$destinationBase."/")) {
					$fs->mirror($sourceBase."/assets/",$destinationBase."/");
				}
			} else if (($source == "*") && ($destination[strlen($source)-1] == "*")) {
				$destination = rtrim($destination,"/*");
				if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
					$fs->mirror($sourceBase."/assets/",$destinationBase."/".$destination);
				}
			} else if ($source[strlen($source)-1] == "*") {
				$source      = rtrim($source,"/*");
				$destination = rtrim($destination,"/*");
				if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
					$fs->mirror($sourceBase."/assets/".$source,$destinationBase."/".$destination);
				}
			} else {
				$pathInfo       = explode("/",$destination);
				$file           = array_pop($pathInfo);
				$destinationDir = implode("/",$pathInfo);
				if (!$fs->exists($destinationBase."/".$destinationDir)) {
					$fs->mkdir($destinationBase."/".$destinationDir);
				}
				if (!self::pathExists($packageName,$destinationBase."/".$destination)) {
					$fs->copy($sourceBase."/assets/".$source,$destinationBase."/".$destination,true);
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
			
			Console::writeLine("<info>the path <path>".$path."</path> already exists. overwrite it with the contents of <path>".$packageName."</path>? Y/n > </info><nophpeol>");
			$answer = strtolower(trim(fgets($stdin)));
			fclose($stdin);
			if ($answer == "y") {
				Console::writeLine("<ok>contents of <path>".$path."</path> being overwritten...</ok>", false, true);
				return false;
			} else {
				Console::writeLine("<warning>contents of <path>".$path."</path> weren't overwritten. some parts of the <path>".$packageName."</path> package may be missing...</warning>", false, true);
				return true;
			}
			
		}
		
		return false;
		
	}
	
	/**
	 * Run the PL tasks when a package is installed
	 * @param  {Object}     a script event object from composer
	 */
	public static function postPackageInstall(Event $event) {
		
		self::runTasks($event);
		
	}
	
	/**
	 * Run the PL tasks when a package is updated
	 * @param  {Object}     a script event object from composer
	 */
	public static function postPackageUpdate(Event $event) {
		
		self::runTasks($event);
		
	}
	
	/**
	 * Make sure certain things are set-up before running composer's install
	 * @param  {Object}     a script event object from composer
	 */
	public static function preInstallCmd(Event $event) {
		
		if (class_exists("\PatternLab\Config")) {
			
			// initialize the console to print out any issues
			Console::init();
			
			// initialize the config
			$baseDir = __DIR__."/../../../";
			Config::init($baseDir,false);
			
			// check directories
			if (!is_dir(Config::$options["sourceDir"])) {
				mkdir(Config::$options["sourceDir"]);
			}
			
			if (!is_dir(Config::$options["packagesDir"])) {
				mkdir(Config::$options["packagesDir"]);
			}
			
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
	 */
	protected static function runTasks($event) {
		
		// make sure pattern lab has been loaded
		if (class_exists("\PatternLab\Config")) {
			
			// initialize the console to print out any issues
			Console::init();
			
			// initialize the config for the pluginDir
			$baseDir = __DIR__."/../../../";
			Config::init($baseDir,false);
			
			// get package info
			$package   = $event->getOperation()->getPackage();
			$extra     = $package->getExtra();
			$type      = $package->getType();
			$name      = $package->getName();
			$path      = Config::$options["packagesDir"]."/".$name;
			
			// make sure we're only evaluating pattern lab packages
			if (strpos($type,"patternlab-") !== false) {
				
				// make sure that it has the name-spaced section of data to be parsed
				if (isset($extra["patternlab"])) {
					
					// rebase $extra
					$extra = $extra["patternlab"];
					
					// move assets to the base directory
					if (isset($extra["assets"]["baseDir"])) {
						self::parseFileList($name,$path,Config::$options["baseDir"],$extra["assets"]["baseDir"]);
					}
					
					// move assets to the public directory
					if (isset($extra["assets"]["publicDir"])) {
						self::parseFileList($name,$path,Config::$options["publicDir"],$extra["assets"]["publicDir"]);
					}
					
					// move assets to the source directory
					if (isset($extra["assets"]["sourceDir"])) {
						self::parseFileList($name,$path,Config::$options["sourceDir"],$extra["assets"]["sourceDir"]);
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
				
			}
			
		}
		
	}
	
}