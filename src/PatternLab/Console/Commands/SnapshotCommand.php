<?php

/*!
 * Console Snapshot Command Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Console\Commands;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Console\Command;
use \PatternLab\Snapshot;

class SnapshotCommand extends Command {
	
	public function __construct() {
		
		parent::__construct();
		
		Console::setCommand("s","snapshot","Take a snapshot of public/","The snapshot command copies the current state of public/ and puts it in snapshots/v*/.");
		Console::setCommandOption("s","d:","dir:","Optional directory path","To add an optional directory path instead of the defaul v*/ path:","example-path/");
		
	}
	
	public function run() {
		
		// run the snapshot command
		$snapshotDir = Console::findCommandOptionValue("d|dir");
		$s = new Snapshot();
		$s->takeSnapshot($snapshotDir);
		
	}
	
}


