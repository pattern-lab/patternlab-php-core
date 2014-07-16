<?php

/*!
 * Console Version Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;

class VersionCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "version";
		
		Console::setCommand($this->command,"Print the version number","The version command prints out the current version of Pattern Lab.","v");
		
	}
	
	public function run() {
		
		print "you're running v".Config::$options["v"]." of the PHP version of Pattern Lab...\n";
		
	}
	
}




