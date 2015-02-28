<?php

/*!
 * Console StarterKit Command Class
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

class StarterKitCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "starterkit";
		
		Console::setCommand($this->command,"Initialize or fetch a specific StarterKit","The StarterKit command downloads StarterKits.","k");
		Console::setCommandOption($this->command,"init","Initialize with a blank StarterKit based on the active PatternEngine.","To initialize your project with a base StarterKit:","i");
		Console::setCommandOption($this->command,"install:","Fetch a specific StarterKit from GitHub.","To fetch a StarterKit from GitHub:","j:","<starterkit-name>");
		
	}
	
	public function run() {
		
		// find the value given to the command
		$init       = Console::findCommandOption("i|init");
		$starterkit = Console::findCommandOptionValue("f|install");
		
		if ($init) {
			$patternEngine = Config::getOption("patternExtension");
			$starterkit    = "pattern-lab/starterkit-".$patternEngine."-base";
		}
		
		if ($starterkit) {
			
			// download the starterkit
			$f = new Fetch();
			$f->fetchStarterKit($starterkit);
			
		} else {
			
			Console::writeHelpCommand($this->command);
			
		}
		
	}
	
}
