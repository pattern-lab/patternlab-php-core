<?php

/*!
 * Generator Class
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Compiles and moves all files in the source/patterns dir to public/patterns dir ONCE.
 *
 */

namespace PatternLab;

use \PatternLab\Annotations;
use \PatternLab\Builder;
use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Data;
use \PatternLab\Dispatcher;
use \PatternLab\FileUtil;
use \PatternLab\PatternData;
use \PatternLab\Timer;
use \PatternLab\Util;

class Generator extends Builder {
	
	/**
	* Use the Builder __construct to gather the config variables
	*/
	public function __construct($config = array()) {
		
		// construct the parent
		parent::__construct($config);
		
	}
	
	/**
	* Pulls together a bunch of functions from builder.lib.php in an order that makes sense
	* @param  {Boolean}       decide if CSS should be parsed and saved. performance hog.
	* @param  {Boolean}       decide if static files like CSS and JS should be moved
	*/
	public function generate($options = array()) {
		
		// double-checks options was properly set
		if (empty($options)) {
			Console::writeError("need to pass options to generate...");
		}
		
		// set the default vars
		$moveStatic    = (isset($options["moveStatic"]))    ? $options["moveStatic"] : true;
		$noCacheBuster = (isset($options["noCacheBuster"])) ? $options["noCacheBuster"] : false;
		$exportFiles   = (isset($options["exportFiles"]))   ? $options["exportFiles"] : false;
		$exportClean   = (isset($options["exportClean"]))   ? $options["exportClean"] : false;
		$watchMessage  = (isset($options["watchMessage"]))  ? $options["watchMessage"] : false;
		$watchVerbose  = (isset($options["watchVerbose"]))  ? $options["watchVerbose"] : false;
		
		if ($noCacheBuster) {
			Config::setOption("cacheBuster",0);
		}
		
		// gather up all of the data to be used in patterns
		Data::gather();
		
		// gather all of the various pattern info
		$options = array();
		$options["exportClean"] = $exportClean;
		$options["exportFiles"] = $exportFiles;
		PatternData::gather($options);
		
		// gather the annotations
		Annotations::gather();
		
		// clean the public directory to remove old files
		if ((Config::getOption("cleanPublic") == "true") && $moveStatic) {
			FileUtil::cleanPublic();
		}
		
		// render out the index and style guide
		$this->generateIndex();
		$this->generateStyleguide();
		$this->generateViewAllPages();
		
		// render out the patterns and move them to public/patterns
		$options = array();
		$options["exportFiles"] = $exportFiles;
		$this->generatePatterns($options);
		
		// render the annotations as a js file
		$this->generateAnnotations();
		
		// move all of the files unless pattern only is set
		if ($moveStatic) {
			$this->moveStatic();
		}
		
		// update the change time so the auto-reload will fire (doesn't work for the index and style guide)
		Util::updateChangeTime();
		
		if ($watchVerbose && $watchMessage) {
			Console::writeLine($watchMessage);
		} else {
			Console::writeLine("your site has been generated...");
			Timer::stop();
		}
		
	}
	
	/**
	* Move static files from source/ to public/
	*/
	protected function moveStatic() {
		
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// note the start of the operation
		$dispatcherInstance->dispatch("generator.moveStaticStart");
		
		// common values
		$publicDir = Config::getOption("publicDir");
		$sourceDir = Config::getOption("sourceDir");
		$ignoreExt = Config::getOption("ie");
		$ignoreDir = Config::getOption("id");
		
		// iterate over all of the other files in the source directory
		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir), \RecursiveIteratorIterator::SELF_FIRST);
		
		// make sure dots are skipped
		$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
		
		foreach($objects as $name => $object) {
			
			// clean-up the file name and make sure it's not one of the pattern lab files or to be ignored
			$fileName = str_replace($sourceDir.DIRECTORY_SEPARATOR,"",$name);
			
			if (($fileName[0] != "_") && (!in_array($object->getExtension(),$ignoreExt)) && (!in_array($object->getFilename(),$ignoreDir))) {
				
				// catch directories that have the ignored dir in their path
				$ignored = FileUtil::ignoreDir($fileName);
				
				// check to see if it's a new directory
				if (!$ignored && $object->isDir() && !is_dir($publicDir."/".$fileName)) {
					mkdir($publicDir."/".$fileName);
				}
				
				// check to see if it's a new file or a file that has changed
				if (!$ignored && $object->isFile() && (!file_exists($publicDir."/".$fileName))) {
					FileUtil::moveStaticFile($fileName);
				}
				
			}
			
		}
		
		// note the end of the operation
		$dispatcherInstance->dispatch("generator.moveStaticEnd");
		
	}
	
}
