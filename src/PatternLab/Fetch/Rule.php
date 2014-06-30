<?php

/*!
 * Fetch Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Fetch;

use \PatternLab\Console;

class Rule {
	
	public $name;
	public $unpack;
	public $writeTo;
	public $longCommand;
	
	public function __construct() {
		
		// nothing here yet
		
	}
	
	/**
	* Test the Fetch Rules to see if a Rule matches the supplied value
	* @param  {String}       the command option
	*
	* @return {Boolean}      whether the test was succesful or not
	*/
	public function test($commandOption) {
		return ($commandOption == $this->commandOption);
	}
	
	/**
	* Set the command line flags for the fetch rules
	* @param  {String}       the name of the command
	*/
	public function setCommandOption($command) {
		$desc   = "Install a ".$this->name;
		$sample = $desc.":";
		$extra  = "github-org/github-repo#tag";
		Console::setCommandOption($command,"z:",$this->longCommand.":",$desc,$sample,$extra);
	}
	
}
