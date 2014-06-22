<?php

/*!
 * Console Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Handles the set-up of the console commands, options, and documentation
 * Heavily influenced by the symfony/console output format
 *
 */

namespace PatternLab;

use \PatternLab\Console\Event as ConsoleEvent;
use \PatternLab\Dispatcher;

class Console {
	
	public  static $commands         = array();
	public  static $commandInstances = array();
	public  static $options          = array();
	public  static $optionsShort     = "";
	public  static $optionsLong      = array();
	private static $self             = "";
	
	public static function run() {
		
		// double-check this is being run from the command line
		if (php_sapi_name() != 'cli') {
			print "The builder script can only be run from the command line.\n";
			exit;
		}
		
		self::$self = $_SERVER["PHP_SELF"];
		
		$event = new ConsoleEvent($options = array());
		Dispatcher::$instance->dispatch("console.loadCommandStart",$event);
		
		// loadCommands
		self::loadCommands();
		
		Dispatcher::$instance->dispatch("console.loadCommandEnd",$event);
		
		// get what was passed on the command line
		self::$options = getopt(self::$optionsShort,self::$optionsLong);
		
		// test and run the given command
		$commandFound = false;
		$commandSent  = self::getCommand();
		foreach (self::$commandInstances as $command) {
			if ($command->test($commandSent)) {
				$command->run();
				$commandFound = true;
			}
		}
		
		// no command was found so just draw the help by default
		if (!$commandFound) {
			
			self::writeHelp();
			
		}
		
	}
	
	/**
	* See if a particular command was passed to the script via the command line and return a boolean. Can either be the short or long version
	* @param  {String}       list of arguments to check
	*
	* @return {Boolean}      if the command has been passed to the script via the command line
	*/
	public static function findCommand($args) {
		$args = explode("|",$args);
		foreach ($args as $arg) {
			if (isset(self::$options[$arg])) {
				return true;
			
			}
		}
		return false;
	}
	
	/**
	* See if a particular command was passed to the script via the command line and return a value. Can either be the short or long version
	* @param  {String}       list of arguments to check
	*
	* @return {String}       the value that was passed via the command line
	*/
	public static function findCommandValue($args) {
		$args = explode("|",$args);
		foreach ($args as $arg) {
			if (isset(self::$options[$arg])) {
				return self::$options[$arg];
			}
		}
		return false;
	}
	
	/**
	* Find the short command for a given long gommand
	* @param  {String}       long command to search for
	*
	* @return {String}       the search command
	*/
	public static function findCommandShort($arg) {
		foreach (self::$commands as $command => $commandOptions) {
			if (($commandOptions["commandLong"] == $arg) || ($commandOptions["commandShort"] == $arg)) {
				return $command;
			}
		}
		return false;
	}
	
	/**
	* Return the command that was given in the command line arguments
	*
	* @return {String}      the command. passes false if no command was found
	*/
	public static function getCommand() {
		foreach (self::$commands as $command => $attributes) {
			if (isset(self::$options[$command]) || isset(self::$options[$attributes["commandLong"]])) {
				return $command;
			}
		}
		return false;
	}
	
	/**
	* Load all of the rules related to Pattern Data
	*/
	public static function loadCommands() {
		foreach (glob(__DIR__."/Console/Commands/*.php") as $filename) {
			$command = str_replace(".php","",str_replace(__DIR__."/Console/Commands/","",$filename));
			if ($command[0] != "_") {
				$commandClass = "\PatternLab\Console\Commands\\".$command;
				self::$commandInstances[] = new $commandClass();
			}
		}
	}
	
	/**
	* Set-up the command so it can be used from the command line
	* @param  {String}       the single character version of the command
	* @param  {String}       the long version of the command
	* @param  {String}       the description to be used in the "available commands" section of writeHelp()
	* @param  {String}       the description to be used in the "help" section of writeHelpCommand()
	*/
	public static function setCommand($short,$long,$desc,$help) {
		self::$optionsShort .= $short;
		self::$optionsLong[] = $long;
		$short = str_replace(":","",$short);
		$long  = str_replace(":","",$long);
		self::$commands[$short] = array("commandShort" => $short, "commandLong" => $long, "commandLongLength" => strlen($long), "commandDesc" => $desc, "commandHelp" => $help, "commandOptions" => array(), "commandExamples" => array());
	}
	
	/**
	* Set a sample for a specific command
	* @param  {String}       the single character of the command that this option is related to
	* @param  {String}       the sample to be used in the "sample" section of writeHelpCommand()
	* @param  {String}       the extra info to be used in the example command for the "sample" section of writeHelpCommand()
	*/
	public static function setCommandSample($command,$sample,$extra) {
		self::$commands[$command]["commandExamples"][] = array("exampleSample" => $sample, "exampleExtra" => $extra);
	}
	
	/**
	* See if a particular option was passed to the script via the command line and return a boolean. Can either be the short or long version
	* @param  {String}      list of arguments to check
	*
	* @return {Boolean}      if the command has been passed to the script via the command line
	*/
	public static function findCommandOption($args) {
		$args = explode("|",$args);
		foreach ($args as $arg) {
			if (isset(self::$options[$arg])) {
				return true;
			}
		}
		return false;
	}

	/**
	* See if a particular option was passed to the script via the command line and return a value. Can either be the short or long version
	* @param  {String}      list of arguments to check
	*
	* @return {String}      the value that was passed via the command line
	*/
	public static function findCommandOptionValue($args) {
		$args = explode("|",$args);
		foreach ($args as $arg) {
			if (isset(self::$options[$arg])) {
				return self::$options[$arg];
			}
		}
		return false;
	}

	/**
	* Set-up an option for a given command so it can be used from the command line
	* @param  {String}       the single character of the command that this option is related to
	* @param  {String}       the single character version of the option
	* @param  {String}       the long version of the option
	* @param  {String}       the description to be used in the "available options" section of writeHelpCommand()
	* @param  {String}       the sample to be used in the "sample" section of writeHelpCommand()
	* @param  {String}       the extra info to be used in the example command for the "sample" section of writeHelpCommand()
	*/
	public static function setCommandOption($command,$short,$long,$desc,$sample,$extra = "") {
		if (strpos(self::$optionsShort,$short) === false) {
			self::$optionsShort .= $short;
		}
		if (!in_array($long,self::$optionsLong)) {
			self::$optionsLong[] = $long;
		}
		$short = str_replace(":","",$short);
		$long  = str_replace(":","",$long);
		self::$commands[$command]["commandOptions"][$short] = array("optionShort" => $short, "optionLong" => $long, "optionLongLength" => strlen($long), "optionDesc" => $desc, "optionSample" => $sample, "optionExtra" => $extra);
	}
	
	/**
	* Write out the generic help
	*/
	public static function writeHelp() {
		
		/*
		
		The generic help follows this format:
		
		Pattern Lab Console Options
		
		Usage:
		  php core/console command [options]
		
		Available commands:
		  --build   (-b)    Build Pattern Lab
		  --watch   (-w)    Build Pattern Lab and watch for changes and rebuild as necessary
		  --version (-v)    Display the version number
		  --help    (-h)    Display this help message.
		
		*/
		
		// find length of longest command
		$lengthLong = 0;
		foreach (self::$commands as $command => $attributes) {
			$lengthLong = ($attributes["commandLongLength"] > $lengthLong) ? $attributes["commandLongLength"] : $lengthLong;
		}
		
		// write out the generic usage info
		self::writeLine("");
		self::writeLine("Pattern Lab Console Options",true);
		self::writeLine("Usage:");
		self::writeLine("  php ".self::$self." command [options]",true);
		self::writeLine("Available commands:");
		
		// write out the commands
		foreach (self::$commands as $command => $attributes) {
			$spacer = self::getSpacer($lengthLong,$attributes["commandLongLength"]);
			self::writeLine("  --".$attributes["commandLong"].$spacer."(-".$attributes["commandShort"].")    ".$attributes["commandDesc"]);
		}
		
		self::writeLine("");
		
	}
	
	/**
	* Write out the command-specific help
	* @param  {String}       the single character of the command that this option is related to
	*/
	public static function writeHelpCommand($command = "") {
		
		/*
		
		The command help follows this format:
		
		Build Command Options
		
		Usage:
		  php core/console --build [--patternsonly|-p] [--nocache|-n] [--enablecss|-c]
		
		Available options:
		  --patternsonly (-p)    Build only the patterns. Does NOT clean public/.
		  --nocache      (-n)    Set the cacheBuster value to 0.
		  --enablecss    (-c)    Generate CSS for each pattern. Resource intensive.
		  --help         (-h)    Display this help message.
		
		Help:
		 The build command builds an entire site a single time. It compiles the patterns and moves content from source/ into public/
		
		 Samples:
		
		   To run and generate the CSS for each pattern:
		     php core/console build -c
		
		   To build only the patterns and not move other files from source/ to public/
		     php core/console build -p
		
		   To turn off the cacheBuster
		     php core/console build -n
		*/
		
		// if given an empty command or the command doesn't exist in the lists give the generic help
		if (empty($command)) {
			self::writeHelp();
			return;
		}
		
		$commandShort      = self::$commands[$command]["commandShort"];
		$commandLong       = self::$commands[$command]["commandLong"];
		$commandHelp       = self::$commands[$command]["commandHelp"];
		$commandExtra      = isset(self::$commands[$command]["commandExtra"]) ? self::$commands[$command]["commandExtra"] : "";
		$commandOptions    = self::$commands[$command]["commandOptions"];
		$commandExamples   = self::$commands[$command]["commandExamples"];
		
		$commandLongUC = ucfirst($commandLong);
		
		// write out the option list and get the longest item
		$optionList = "";
		$lengthLong = 0;
		foreach ($commandOptions as $option => $attributes) {
			$optionList .= "[--".$attributes["optionLong"]."|-".$attributes["optionShort"]."] ";
			$lengthLong = ($attributes["optionLongLength"] > $lengthLong) ? $attributes["optionLongLength"] : $lengthLong;
		}
		
		// write out the generic usage info
		self::writeLine("");
		self::writeLine($commandLongUC." Command Options",true);
		self::writeLine("Usage:");
		self::writeLine("  php ".self::$self." --".$commandLong."|-".$commandShort." ".$optionList,true);
		
		// write out the available options
		if (count($commandOptions) > 0) {
			self::writeLine("Available options:");
			foreach ($commandOptions as $option => $attributes) {
				$spacer = self::getSpacer($lengthLong,$attributes["optionLongLength"]);
				self::writeLine("  --".$attributes["optionLong"].$spacer."(-".$attributes["optionShort"].")    ".$attributes["optionDesc"]);
			}
			self::writeLine("");
		}
		
		self::writeLine("Help:");
		self::writeLine("  ".$commandHelp,true);
		
		// write out the samples
		if ((count($commandOptions) > 0) || (count($commandExamples) > 0)) {
			self::writeLine("  Samples:",true);
		}
		
		if (count($commandExamples) > 0) {
			foreach ($commandExamples as $example => $attributes) {
				self::writeLine("   ".$attributes["exampleSample"]);
				self::writeLine("     php ".self::$self." --".$commandLong." ".$attributes["exampleExtra"]);
				self::writeLine("     php ".self::$self." -".$commandShort." ".$attributes["exampleExtra"],true);
			}
		}
		
		if (count($commandOptions) > 0) {
			foreach ($commandOptions as $option => $attributes) {
				self::writeLine("   ".$attributes["optionSample"]);
				self::writeLine("     php ".self::$self." --".$commandLong." --".$attributes["optionLong"]." ".$attributes["optionExtra"]);
				self::writeLine("     php ".self::$self." -".$commandShort." -".$attributes["optionShort"]." ".$attributes["optionExtra"],true);
			}
		}
		
	}
	
	/**
	* Write out a line of the help
	* @param  {Boolean}       handle double-break
	*/
	protected static function writeLine($line,$doubleBreak = false) {
		$break = ($doubleBreak) ? "\n\n" : "\n";
		print "  ".$line.$break;
	}
	
	/**
	* Make sure the space is properly set between long command options and short command options
	* @param  {Integer}       the longest length of the command's options
	* @param  {Integer}       the character length of the given option
	*/
	protected static function getSpacer($lengthLong,$itemLongLength) {
		$i            = 0;
		$spacer       = " ";
		$spacerLength = $lengthLong - $itemLongLength;
		while ($i < $spacerLength) {
			$spacer .= " ";
			$i++;
		}
		return $spacer;
	}
	
}
