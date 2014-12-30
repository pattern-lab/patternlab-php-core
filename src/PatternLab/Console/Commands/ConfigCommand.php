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
			
			// get all of the options
			$options = Config::getOptions();
			
			// sort 'em alphabetically
			ksort($options);
			
			// find length of longest option
			$lengthLong = 0;
			foreach ($options as $optionName => $optionValue) {
				$lengthLong = (strlen($optionName) > $lengthLong) ? strlen($optionName) : $lengthLong;
			}
			
			// iterate over each option and spit it out
			foreach ($options as $optionName => $optionValue) {
				$optionValue = (is_array($optionValue)) ? implode(", ",$optionValue) : $optionValue;
				$optionValue = (!$optionValue) ? "false" : $optionValue;
				$spacer      = Console::getSpacer($lengthLong,strlen($optionName));
				Console::writeLine("<info>".$optionName.":</info>".$spacer.$optionValue);
			}
			
		} else if (Console::findCommandOption("get")) {
			
			// figure out which optino was passed
			$searchOption = Console::findCommandOptionValue("get");
			$optionValue  = Config::getOption($searchOption);
			
			// write it out
			if (!$optionValue) {
				Console::writeError("the --get value you provided, <info>".$searchOption."</info>, does not exists in the config...");
			} else {
				$optionValue = (is_array($optionValue)) ? implode(", ",$optionValue) : $optionValue;
				$optionValue = (!$optionValue) ? "false" : $optionValue;
				Console::writeInfo($searchOption.": <ok>".$optionValue."</ok>");
			}
			
		} else if (Console::findCommandOption("set")) {
			
			// find the value that was passed
			$updateOption = Console::findCommandOptionValue("set");
			$updateOptionBits = explode("=",$updateOption);
			if (count($updateOptionBits) == 1) {
				Console::writeError("the --set value should look like <info>optionName=\"optionValue\"</info>. nothing was updated...");
			} 
			
			// set the name and value that were passed
			$updateName   = $updateOptionBits[0];
			$updateValue  = (($updateOptionBits[1][0] == "\"") || ($updateOptionBits[1][0] == "'")) ? substr($updateOptionBits[1],1,strlen($updateOptionBits[1])-1) : $updateOptionBits[1];
			
			// make sure the option being updated already exists
			$currentValue = Config::getOption($updateName);
			
			if (!$currentValue) {
				Console::writeError("the --set option you provided, <info>".$updateName."</info>, does not exists in the config. nothing will be updated...");
			} else {
				Config::updateConfigOption($updateName,$updateValue);
			}
			
		} else {
			
			// no acceptable options were passed so write out the help
			Console::writeHelpCommand($this->command);
			
		}
		
	}
	
}
