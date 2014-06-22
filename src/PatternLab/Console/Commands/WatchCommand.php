<?php

/*!
 * Console Watch Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Generator;
use \PatternLab\Watcher;

class WatchCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		Console::setCommand("w","watch","Watch for changes and regenerate","The watch command builds Pattern Lab, watches for changes in source/ and regenerates Pattern Lab when there are any.");
		Console::setCommandOption("w","p","patternsonly","Watches only the patterns. Does NOT clean public/.","To watch and generate only the patterns:");
		Console::setCommandOption("w","n","nocache","Set the cacheBuster value to 0.","To turn off the cacheBuster:");
		Console::setCommandOption("w","r","autoreload","Turn on the auto-reload service.","To turn on auto-reload:");
		
	}
	
	public function run() {
		
		// set-up required vars
		$enableCSS     = Console::findCommandOption("c|enablecss");
		$moveStatic    = (Console::findCommandOption("p|patternsonly")) ? false : true;
		$noCacheBuster = Console::findCommandOption("n|nocache");
		
		// CSS feature should't be used with watch
		$enableCSS = false;
		
		// load the generator
		$g = new Generator();
		$g->generate($enableCSS,$moveStatic,$noCacheBuster);
		
		// load the watcher
		$w = new Watcher();
		$w->watch($autoReload,$moveStatic,$noCacheBuster);
		
	}
	
}
