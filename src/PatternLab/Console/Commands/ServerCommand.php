<?php

/*!
 * Console Server Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Console\Commands\WatchCommand;
use \PatternLab\Console\ProcessSpawner;
use \PatternLab\Timer;


class ServerCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->command = "server";
		
		Console::setCommand($this->command,"Start the PHP-based server","The server command will start PHP's web server for you.","s");
		Console::setCommandOption($this->command,"host:","Provide a custom hostname. Default value is <path>localhost</path>.","To use a custom hostname and the default port:","","<host>");
		Console::setCommandOption($this->command,"port:","Provide a custom port. Default value is <path>8080</path>.","To use a custom port and the default hostname:","","<port>");
		Console::setCommandOption($this->command,"quiet","Turn on quiet mode for the server.","To turn on quiet mode:");
		Console::setCommandOption($this->command,"with-watch","Start watching ./source when starting the server. Takes the same arguments as --watch.","To turn on with-watch mode:");
		Console::setCommandSample($this->command,"To provide both a custom hostname and port:","--host <host> --port <port>");

	}
	
	public function run() {
		
		if (version_compare(phpversion(), '5.4.0', '<')) {
			
			Console::writeWarning("you must have PHP 5.4.0 or greater to use this feature. you are using PHP ".phpversion()."...");
			
		} else {
			
			// set-up defaults
			$publicDir = Config::getOption("publicDir");
			$coreDir   = Config::getOption("coreDir");
			
			$host  = Console::findCommandOptionValue("host");
			$host  = $host ? $host : "localhost";
			
			$port  = Console::findCommandOptionValue("port");
			$host  = $port ? $host.":".$port : $host.":8080";
			
			$quiet = Console::findCommandOption("quiet");
			
			// set-up the base command
			$command    = $this->pathPHP." -S ".$host." ".$coreDir."/server/router.php";
			$commands   = array();
			$commands[] = array("command" => $command, "cwd" => $publicDir, "timeout" => null, "idle" => 1800);
			
			// get the watch command info
			if (Console::findCommandOption("with-watch")) {
				$watchCommand = new WatchCommand;
				$commands[]   = array("command" => $watchCommand->build()." --no-procs", "timeout" => null, "idle" => 1800);
			}
			
			Console::writeInfo("server started on http://".$host." - use ctrl+c to exit...");
			
			$processSpawner = new ProcessSpawner;
			$processSpawner->spawn($commands, $quiet);
			
		}
		
	}
	
	public function build() {
		
		$command = $this->pathPHP." ".$this->pathConsole." --".$this->command;
		
		$host = Console::findCommandOptionValue("host");
		$port = Console::findCommandOptionValue("port");
		
		if ($host) {
			$command .= " --host ".$host;
		}
		if ($port) {
			$command .= " --port ".$port;
		}
		
		return $command;
		
	}
	
}
