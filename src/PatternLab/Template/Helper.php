<?php

/*!
 * Template Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Set-ups the vars for the template loader
 *
 */

namespace PatternLab\Template;

use \PatternLab\Config;
use \PatternLab\Template\Loader;

class Helper {
	
	public static $htmlHead;
	public static $htmlFoot;
	public static $patternHead;
	public static $patternFoot;
	public static $filesystemLoader;
	public static $htmlLoader;
	
	/**
	* Set-up default vars
	*/
	public static function init() {
		
		// load pattern-lab's resources
		$partialPath            = Config::$options["pluginDir"]."/".Config::$options["styleguideKit"]."/views/partials";
		self::$htmlHead         = file_get_contents($partialPath."/general-header.mustache");
		self::$htmlFoot         = file_get_contents($partialPath."/general-footer.mustache");
		
		// gather the user-defined header and footer information
		$patternHeadPath        = Config::$options["sourceDir"]."/_meta/_00-head.".Config::$options["patternExtension"];
		$patternFootPath        = Config::$options["sourceDir"]."/_meta/_01-foot.".Config::$options["patternExtension"];
		self::$patternHead      = (file_exists($patternHeadPath)) ? file_get_contents($patternHeadPath) : "";
		self::$patternFoot      = (file_exists($patternFootPath)) ? file_get_contents($patternFootPath) : "";
		
		// add pattern lab's resource to the user-defined files
		//self::$patternHead      = str_replace("{% pattern-lab-head %}",$htmlHead,$patternHead);
		//self::$patternFoot      = str_replace("{% pattern-lab-foot %}",$extraFoot.$htmlFoot,$patternFoot);
		//self::$mainPageHead     = self::$patternHead;
		//self::$mainPageFoot     = str_replace("{% pattern-lab-foot %}",$htmlFoot,$patternFoot);
		
		// add the generic loaders
		$templateLoader         = new Loader();
		self::$filesystemLoader = $templateLoader->fileSystem();
		self::$htmlLoader       = $templateLoader->vanilla();
		
	}
	
}