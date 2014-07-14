<?php

/*!
 * Template Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Set-ups the vars needed related to setting up and rendering templates. Meaning putting 
 *
 */

namespace PatternLab;

use \PatternLab\Config;

class Template {
	
	public static $htmlHead;
	public static $htmlFoot;
	public static $patternHead;
	public static $patternFoot;
	public static $filesystemLoader;
	public static $htmlLoader;
	public static $patternLoader;
	
	/**
	* Set-up default vars
	*/
	public static function init() {
		
		// make sure config vars exist
		if (!isset(Config::$options["patternExtension"])) {
			print "no pattern extension. make sure you've loaded one.\n";
			exit;
		}
		
		if (!isset(Config::$options["styleguideKit"])) {
			print "no styleguideKit to use. please add it to the config.\n";
			exit;
		}
		
		// set-up config vars
		$patternExtension        = Config::$options["patternExtension"];
		$pluginDir               = Config::$options["pluginDir"];
		$sourceDir               = Config::$options["sourceDir"];
		$styleguideKit           = Config::$options["styleguideKit"];
		
		// load pattern-lab's resources
		$partialPath             = $pluginDir."/".$styleguideKit."/views/partials";
		self::$htmlHead          = file_get_contents($partialPath."/general-header.".$patternExtension);
		self::$htmlFoot          = file_get_contents($partialPath."/general-footer.".$patternExtension);
		
		// gather the user-defined header and footer information
		$patternHeadPath         = $sourceDir."/_meta/_00-head.".$patternExtension;
		$patternFootPath         = $sourceDir."/_meta/_01-foot.".$patternExtension;
		self::$patternHead       = (file_exists($patternHeadPath)) ? file_get_contents($patternHeadPath) : "";
		self::$patternFoot       = (file_exists($patternFootPath)) ? file_get_contents($patternFootPath) : "";
		
		// add the generic loaders
		$options                 = array();
		$options["templatePath"] = $pluginDir."/".$styleguideKit."/views";
		$options["partialsPath"] = $pluginDir."/".$styleguideKit."/views/partials";
		self::$filesystemLoader  = PatternEngine::$instance->getFileSystemLoader($options);
		self::$htmlLoader        = PatternEngine::$instance->getVanillaLoader();
		
	}
	
}