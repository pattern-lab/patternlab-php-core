<?php

/*!
 * Process Spawner Event Class
 *
 * Copyright (c) 2016 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Handle the process needs of plugins
 *
 */

namespace PatternLab\Console;

use \Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class ProcessSpawnerEvent extends SymfonyEvent {
	
	protected $pluginProcesses;
	
	public function __construct() {
		$this->pluginProcesses = array();
	}
	
	public function addPluginProcesses($processes = array()) {
		if (!empty($processes)) {
			$this->pluginProcesses = array_merge($this->pluginProcesses, $processes);
		}
	}
	
	public function getPluginProcesses() {
		return $this->pluginProcesses;
	}
	
}
