<?php

/*!
 * Dispatcher Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Dispatches events for Pattern Lab that can be listened to by plug-ins
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \Symfony\Component\EventDispatcher\EventDispatcher;

class Dispatcher {
	
	public static $instance;
	
	/**
	* Check to see if the given pattern type has a pattern subtype associated with it
	* @param  {String}        the name of the pattern
	*
	* @return {Boolean}       if it was found or not
	*/
	public static function init() {
		
		self::$instance = new EventDispatcher();
		self::loadListeners();
		
	}
	
	/**
	* Load listeners that may be a part of plug-ins that should be notified by the dispatcher
	*/
	protected static function loadListeners() {
		
		if (!is_dir(Config::$options["packagesDir"])) {
			Console::writeLine("<error>you haven't fully set-up Pattern Lab yet. please add a pattern engine...</error>");
			exit;
		}
		
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Config::$options["packagesDir"]), \RecursiveIteratorIterator::CHILD_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			if ((strpos($name,"PatternLabListener.php") !== false) && (strpos($name,"plugins/vendor/") === false)) {
				$dirs              = explode("/",$object->getPath());
				$listenerName      = "\\".$dirs[count($dirs)-2]."\\".$dirs[count($dirs)-1]."\\".str_replace(".php","",$object->getFilename());
				$listener          = new $listenerName();
				foreach ($listener->listeners as $event => $eventProps) {
					$eventPriority = (isset($eventProps["priority"])) ? $eventProps["priority"] : 0;
					self::$instance->addListener($event, array($listener, $eventProps["callable"]), $eventPriority);
				}
			}
			
		}
		
	}
	
}
