<?php

/*!
 * Annotations Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Acts as the store for annotations. Parse annotations.js and *.md files found in source/_annotations
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Dispatcher;
use \PatternLab\Parsers\Documentation;
use \PatternLab\Timer;
use \Symfony\Component\Finder\Finder;
use \Symfony\Component\Yaml\Exception\ParseException;
use \Symfony\Component\Yaml\Yaml;

class Annotations {
	
	protected static $store = array();
	
	/**
	* Clear all of the data in the $store
	*/
	public static function clear() {
		self::$store = array();
	}
	
	/**
	* Gather data from annotations.js and *.md files found in source/_annotations
	*
	* @return {Array}        populates Annotations::$store
	*/
	public static function gather() {
		
		// set-up default var
		$sourceDir = Config::getOption("sourceDir");
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// dispatch that the data gather has started
		$dispatcherInstance->dispatch("annotations.gatherStart");
		
		// set-up the comments store
		self::$store["comments"] = array();
		
		// iterate over all of the files in the annotations dir
		if (!is_dir($sourceDir."/_annotations")) {
			Console::writeWarning("<path>_annotations/</path><warning> doesn't exist so you won't have annotations...");
			mkdir($sourceDir."/_annotations");
		}
		
		// find the markdown-based annotations
		$finder = new Finder();
		$finder->files()->name("*.md")->in($sourceDir."/_annotations");
		$finder->sortByName();
		
		foreach ($finder as $name => $file) {
				
			$data    = array();
			$data[0] = array();
			
			$text = file_get_contents($file->getPathname());
			
			$matches = (strpos($text,PHP_EOL."~*~".PHP_EOL) !== false) ? explode(PHP_EOL."~*~".PHP_EOL,$text) : array($text);
			
			foreach ($matches as $match) {
				
				list($yaml,$markdown) = Documentation::parse($match);
				
				if (isset($yaml["el"]) || isset($yaml["selector"])) {
					$data[0]["el"]  = (isset($yaml["el"])) ? $yaml["el"] : $yaml["selector"];
				} else {
					$data[0]["el"]  = "#someimpossibleselector";
				}
				$data[0]["title"]   = isset($yaml["title"]) ? $yaml["title"] : "";
				$data[0]["comment"] = $markdown;
				
				self::$store["comments"] = array_merge(self::$store["comments"],$data);
				
			}
			
		}
		
		// read in the old style annotations.js, modify the data and generate JSON array to merge
		if (file_exists($sourceDir."/_annotations/annotations.js")) {
			$text = file_get_contents($sourceDir."/_annotations/annotations.js");
			$text = str_replace("var comments = ","",$text);
			$text = rtrim($text,";");
			$data = json_decode($text,true);
			if ($jsonErrorMessage = JSON::hasError()) {
				JSON::lastErrorMsg("_annotations/annotations.js",$jsonErrorMessage,$data);
			}
		}
		
		// merge in any data from the old file
		self::$store["comments"] = array_merge(self::$store["comments"],$data["comments"]);
		
		$dispatcherInstance->dispatch("annotations.gatherEnd");
		
	}
	
	/**
	* Get the data in the store
	*/
	public static function get() {
		return self::$store;
	}
	
}
