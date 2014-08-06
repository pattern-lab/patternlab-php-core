<?php

/*!
 * Console Config Command Class
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
use \PatternLab\Timer;

class ConfigCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "config";
		
		Console::setCommand($this->command,"Configure Pattern Lab","The --config command allows for the review and update of existing Pattern Lab config options.","c");
		Console::setCommandOption($this->command,"get:","Get the value for a specific config option.","To get a configuration option:","","configOption");
		Console::setCommandOption($this->command,"list","List the current config options.","To list the current configuration:");
		Console::setCommandOption($this->command,"set:","Set the value for a specific config option.","To set a configuration option:","","configOption=\"configValue\"");
		
	}
	
	public function run() {
		
		if (Console::findCommandOption("list")) {
			
			$options = Config::get();
			
			ksort($options);
			
			// find length of longest option
			$lengthLong = 0;
			foreach ($options as $optionName => $optionValue) {
				$lengthLong = (strlen($optionName) > $lengthLong) ? strlen($optionName) : $lengthLong;
			}
			
			// iterate over each option
			foreach ($options as $optionName => $optionValue) {
				$optionValue = (is_array($optionValue)) ? implode(", ",$optionValue) : $optionValue;
				$optionValue = (!$optionValue) ? "false" : $optionValue;
				$spacer      = Console::getSpacer($lengthLong,strlen($optionName));
				Console::writeInfo($optionName.":".$spacer."<ok>".$optionValue."</ok>");
			}
			
		} else if (Console::findCommandOption("get")) {
			
			$searchOption = Console::findCommandOptionValue("get");
			$optionValue  = Config::getOption($searchOption);
			if (!$optionValue) {
				Console::writeError("the search config option you provided, <info>".$searchOption."</info>, does not exists in the config...");
			} else {
				$optionValue = (is_array($optionValue)) ? implode(", ",$optionValue) : $optionValue;
				$optionValue = (!$optionValue) ? "false" : $optionValue;
				Console::writeInfo($searchOption.": <ok>".$optionValue."</ok>");
			}
			
		} else if (Console::findCommandOption("set")) {
			
		} else {
			
		}
		
	}
	
}
