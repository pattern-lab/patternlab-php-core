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
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Installer {
	
	/**
	 * Make sure certain things are set-up before running composer's install
	 */
	public static function preInstallCmd(Event $event) {
		
		if (class_exists("\PatternLab\Config")) {
			
			$baseDir = __DIR__."/../../../";
			Config::init($baseDir,false);
			
			if (!is_dir(Config::$options["sourceDir"])) {
				mkdir(Config::$options["sourceDir"]);
			}
			
			if (!is_dir(Config::$options["pluginDir"])) {
				mkdir(Config::$options["pluginDir"]);
			}
			
		}
		
	}
	
	/**
	 * Handle some Pattern Lab specific tasks based on what's found in the package's composer.json file
	 */
	public static function postPackageInstall(Event $event) {
		
		if (class_exists("\PatternLab\Config")) {
			
			// initialize the config for the pluginDir
			$baseDir = __DIR__."/../../../";
			Config::init($baseDir,false);
			
			// get package info
			$package   = $event->getOperation()->getPackage();
			$extra     = $package->getExtra();
			$type      = $package->getType();
			$path      = Config::$options["pluginDir"]."/".$package->getName();
			
			// make sure we're only evaluating pattern lab packages
			if (strpos($type,"patternlab-") !== false) {
				
				// make sure that it has the name-spaced section of data to be parsed
				if (isset($extra["patternlab"])) {
					
					// rebase $extra
					$extra = $extra["patternlab"];
					
					// move assets to the base directory
					if (isset($extra["assets"]["baseDir"])) {
						self::parseFileList($path,Config::$options["baseDir"],$extra["assets"]["baseDir"]);
					}
					
					// move assets to the public directory
					if (isset($extra["assets"]["publicDir"])) {
						self::parseFileList($path,Config::$options["publicDir"],$extra["assets"]["publicDir"]);
					}
					
					// move assets to the source directory
					if (isset($extra["assets"]["sourceDir"])) {
						self::parseFileList($path,Config::$options["sourceDir"],$extra["assets"]["sourceDir"]);
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
	
	/**
	 * Move the files from the package to their location in the public dir or source dir
	 * @param  {String}    the base directory for the source of the files
	 * @param  {String}    the base directory for the destintation of the files (publicDir or sourceDir)
	 * @param  {Array}     the list of files to be moved
	 */
	protected static function parseFileList($sourceBase,$destinationBase,$fileList) {
		
		$fs = new Filesystem();
		
		foreach ($fileList as $fileItem) {
			
			// retrieve the source & destination
			$source      = key($fileItem);
			$destination = $fileItem[$source];
			
			// depending on the source handle things differently. mirror if it ends in /*
			if (($source == "*") && ($destination == "*")) {
				$fs->mirror($sourceBase."/assets/",$destinationBase."/");
			} else if (($source == "*") && ($destination[strlen($source)-1] == "*")) {
				$destination = rtrim($destination,"/*");
				$fs->mirror($sourceBase."/assets/",$destinationBase."/".$destination);
			} else if ($source[strlen($source)-1] == "*") {
				$source      = rtrim($source,"/*");
				$destination = rtrim($destination,"/*");
				$fs->mirror($sourceBase."/assets/".$source,$destinationBase."/".$destination);
			} else {
				$pathInfo       = explode("/",$destination);
				$file           = array_pop($pathInfo);
				$destinationDir = implode("/",$pathInfo);
				if (!$fs->exists($destinationBase.$destinationDir)) {
					$fs->mkdir($destinationBase."/".$destinationDir);
				}
				$fs->copy($sourceBase."/assets/".$source,$destinationBase."/".$destination,true);
			}
			
		}
		
	}
	
}