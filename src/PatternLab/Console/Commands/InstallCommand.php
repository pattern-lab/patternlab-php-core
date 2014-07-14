<?php

/*!
 * Console Install Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;

class InstallCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "i";
		
		Console::setCommand($this->command,"install","Install Pattern Lab's dependencies","The install command will run the included version of Composer to install Pattern Lab's dependencies.");
		
	}
	
	public function run() {
		
		// run composer
		$composerPath = Config::$options["baseDir"]."/core/bin/composer.phar";
		passthru("php ".$composerPath." require ".$package);
		
	}
	
}




