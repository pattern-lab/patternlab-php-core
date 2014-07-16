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
		
		$this->command = "watch";
		
		Console::setCommand($this->command,"Watch for changes and regenerate","The watch command builds Pattern Lab, watches for changes in source/ and regenerates Pattern Lab when there are any.","w");
		Console::setCommandOption($this->command,"patternsonly","Watches only the patterns. Does NOT clean public/.","To watch and generate only the patterns:","p");
		Console::setCommandOption($this->command,"nocache","Set the cacheBuster value to 0.","To turn off the cacheBuster:","n");
		Console::setCommandOption($this->command,"autoreload","Turn on the auto-reload service.","To turn on auto-reload:","r");
		
	}
	
	public function run() {
		
		// set-up required vars
		$options                  = array();
		$options["moveStatic"]    = (Console::findCommandOption("p|patternsonly")) ? false : true;
		$options["noCacheBuster"] = Console::findCommandOption("n|nocache");
		$options["autoReload"]    = Console::findCommandOption("r|autoreload");
		
		// load the generator
		$g = new Generator();
		$g->generate($options);
		
		// load the watcher
		$w = new Watcher();
		$w->watch($options);
		
	}
	
}
