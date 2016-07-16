<?php

/*!
 * Process Spawner
 *
 * Copyright (c) 2016 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Provide a single instance of spawning background processes related to Pattern Lab
 * Hopefully makes ctrl+c truly clean-up the mess of background processes
 *
 */

namespace PatternLab\Console;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Commands\WatchCommand;
use \PatternLab\Console\ProcessSpawnerEvent;
use \PatternLab\Dispatcher;
use \PatternLab\Timer;
use \Symfony\Component\Process\Exception\ProcessTimedOutException;
use \Symfony\Component\Process\Process;


class ProcessSpawner {
	
	protected $pluginProcesses;
	
	/**
	* Set-up a default var
	*/
	public function __construct() {
		
		// dispatch event and build the appropriate processes
		$event = new ProcessSpawnerEvent();
		$dispatcherInstance = Dispatcher::getInstance();
		$dispatcherInstance->dispatch('processSpawner.getPluginProcesses',$event);
		$this->pluginProcesses = $event->getPluginProcesses();
		
	}
	
	/**
	* Spawn the passed commands and those collected from plugins
	* @param  {Array}       a list of commands to spawn
	* @param  {Boolean}     if this should be run in quiet mode
	*/
	public function spawn($commands = array(), $quiet = false) {
		
		// set-up a default
		$processes = array();
		
		// add the default processes sent to the spawner
		if (!empty($commands)) {
			foreach ($commands as $command) {
				$processes[] = $this->buildProcess($command);
			}
		}
		
		// add the processes sent to the spawner from plugins
		foreach ($this->pluginProcesses as $pluginProcess) {
			$processes[] = $this->buildProcess($pluginProcess);
		}
		
		// if there are processes to spawn do so
		if (!empty($processes)) {
			
			// start the processes
			foreach ($processes as $process) {
				$process["process"]->start();
			}
			
			// check on them and produce output
			while (true) {
				foreach ($processes as $process) {
					try {
						if ($process["process"]->isRunning()) {
							$process["process"]->checkTimeout();
							if (!$quiet && $process["output"]) {
								print $process["process"]->getIncrementalOutput();
								$cmd = $process["process"]->getCommandLine();
								if (strpos($cmd,"router.php") != (strlen($cmd) - 10)) {
									print $process["process"]->getIncrementalErrorOutput();
								}
							}
						}
					} catch (ProcessTimedOutException $e) {
						if ($e->isGeneralTimeout()) {
							Console::writeError("pattern lab processes should never time out. yours did...");
						} else if ($e->isIdleTimeout()) {
							Console::writeError("pattern lab processes automatically time out if their is no command line output in 30 minutes...");
						}
					}
				}
				usleep(100000);
			}
		}
		
	}
	
	/**
	* Build the process from the given commandOptions
	* @param  {Array}       the options from which to build the process
	*/
	protected function buildProcess($commandOptions) {
		
		if (is_string($commandOptions)) {
			
			$process = new Process(escapeshellcmd((string) $commandOptions));
			return array("process" => $process, "output" => true);
			
		} else if (is_array($commandOptions)) {
			
			$commandline = escapeshellcmd((string) $commandOptions["command"]);
			$cwd         = isset($commandOptions["cwd"])     ? $commandOptions["cwd"]     : null;
			$env         = isset($commandOptions["env"])     ? $commandOptions["env"]     : null;
			$input       = isset($commandOptions["input"])   ? $commandOptions["input"]   : null;
			$timeout     = isset($commandOptions["timeout"]) ? $commandOptions["timeout"] : null;
			$options     = isset($commandOptions["options"]) ? $commandOptions["options"] : array();
			$idle        = isset($commandOptions["idle"])    ? $commandOptions["idle"]    : null;
			$output      = isset($commandOptions["output"])  ? $commandOptions["output"]  : true;
			
			$process = new Process($commandline, $cwd, $env, $input, $timeout, $options);
			
			// double-check idle
			if (!empty($idle)) {
				$process->setIdleTimeout($idle);
			}
			
			return array("process" => $process, "output" => $output);
			
		}
		
	}
	
}
