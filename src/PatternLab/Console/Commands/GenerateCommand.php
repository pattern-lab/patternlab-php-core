<?php

/*!
 * Console Generate Command Class
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

class GenerateCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "g";
		
		Console::setCommand($this->command,"generate","Generate Pattern Lab","The generate command generates an entire site a single time. By default it removes old content in public/, compiles the patterns and moves content from source/ into public/");
		Console::setCommandOption($this->command,"p","patternsonly","Generate only the patterns. Does NOT clean public/.","To generate only the patterns:");
		Console::setCommandOption($this->command,"n","nocache","Set the cacheBuster value to 0.","To turn off the cacheBuster:");
		Console::setCommandOption($this->command,"c","enablecss","Generate CSS for each pattern. Resource intensive.","To run and generate the CSS for each pattern:");
		
	}
	
	public function run() {
		
		// set-up required vars
		$enableCSS     = Console::findCommandOption("c|enablecss");
		$moveStatic    = (Console::findCommandOption("p|patternsonly")) ? false : true;
		$noCacheBuster = Console::findCommandOption("n|nocache");
		
		$g = new Generator();
		$g->generate($enableCSS,$moveStatic,$noCacheBuster);
		$g->printSaying();
		
	}
	
}
