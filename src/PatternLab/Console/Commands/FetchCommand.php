<?php

/*!
 * Console Fetch Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Fetch;
use \PatternLab\Timer;

class FetchCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "fetch:";
		
		Console::setCommand($this->command,"Fetch a package","The fetch command grabs a package from GitHub and installs the package and any package dependencies.","f:");
		Console::setCommandSample($this->command,"To fetch a package:","<package-name>");
		
	}
	
	public function run() {
		
		// run the fetch command
		$p = Console::findCommandValue("f|fetch");
		$f = new Fetch();
		$f->fetch($p);
		
	}
	
}
