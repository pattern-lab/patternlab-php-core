<?php

/*!
 * Console Help Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Console;
use \PatternLab\Console\Command;

class HelpCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "h:";
		
		Console::setCommand($this->command,"help:","Print the help for a given command","The help command prints out the help for a given flag. Just use -h with another command and it will tell you all of the options.");
		
	}
	
	public function run() {
		
		if ($helpCommand = Console::findCommandValue("h|help")) {
			$helpCommand = str_replace("-","",$helpCommand);
			if ($commandFound = Console::findCommandShort($helpCommand)) {
				Console::writeHelpCommand($commandFound);
			} else {
				Console::writeHelp();
			}
		} else {
			Console::writeHelp();
		}
		
	}
	
}
