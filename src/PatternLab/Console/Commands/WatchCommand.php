<?php

/*!
 * Console Watch Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Console\ProcessSpawner;
use \PatternLab\Generator;
use \PatternLab\Timer;
use \PatternLab\Watcher;

class WatchCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "watch";
		
		Console::setCommand($this->command,"Watch for changes and regenerate","The watch command builds Pattern Lab, watches for changes in <path>source/</path> and regenerates Pattern Lab when there are any.","w");
		Console::setCommandOption($this->command,"patternsonly","Watches only the patterns. Does NOT clean <path>public/</path>.","To watch and generate only the patterns:","p");
		Console::setCommandOption($this->command,"nocache","Set the cacheBuster value to 0.","To watch and turn off the cache buster:","n");
		Console::setCommandOption($this->command,"sk","Watch for changes to the StarterKit and copy to <path>source/</path>. The <info>--sk</info> flag should only be used if one is actively developing a StarterKit.","To watch for changes to the StarterKit:");
		Console::setCommandOption($this->command,"no-procs","Disable plug-in related processes. For use with <info>--server --with-watch</info>.","To disable plug-in related processes:");
		Console::setCommandSample($this->command,"To watch only patterns and turn off the cache buster:","--patternsonly --nocache");
		
	}
	
	public function run() {
		
		// set-up required vars
		$options                  = array();
		$options["moveStatic"]    = (Console::findCommandOption("p|patternsonly")) ? false : true;
		$options["noCacheBuster"] = Console::findCommandOption("n|nocache");
		
		// see if the starterKit flag was passed so you know what dir to watch
		if (Console::findCommandOption("sk")) {
			
			// load the starterkit watcher
			$w = new Watcher();
			$w->watchStarterKit();
			
		} else {
			
			if (Console::findCommandOption("no-procs")) {
				
				// don't have to worry about loading processes so launch watcher
				
				// load the generator
				$g = new Generator();
				$g->generate($options);
				
				// load the watcher
				$w = new Watcher();
				$w->watch($options);
				
			} else {
				
				// a vanilla --watch command needs to have a --no-procs version built
				// so we don't get caught in while() loops. re-request the console command
				$commands     = array();
				$commands[]   = array("command" => $this->build()." --no-procs", "timeout" => null, "idle" => 600);
				
				Console::writeInfo("spawning the watch process...");
				
				$process = new ProcessSpawner;
				$process->spawn($commands);
				
			}
			
		}
		
	}
	
	public function build() {
		
		$command = $this->pathPHP." ".$this->pathConsole." --".$this->command;
		
		if (Console::findCommandOption("p|patternsonly")) {
			$command .= " --patternsonly";
		}
		if (Console::findCommandOption("n|nocache")) {
			$command .= " --nocache";
		}
		
		return $command;
		
	}
	
}
