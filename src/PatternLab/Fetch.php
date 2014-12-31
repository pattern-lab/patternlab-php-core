<?php

/*!
 * Fetch Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Copy a package from GitHub and put it in it's appropriate location
 *
 */

namespace PatternLab;

use \PatternLab\Console;
use \PatternLab\Timer;

class Fetch {
	
	/**
	 * Fetch a package using Composer
	 * @param  {String}    the path to the package to be downloaded
	 *
	 * @return {String}    the modified file contents
	 */
	public function fetch($package = "") {
		
		if (empty($package)) {
			Console::writeError("please provide a path for the package before trying to fetch it...");
		}
		
		// run composer
		$composerPath = Config::getOption("coreDir").DIRECTORY_SEPARATOR."bin/composer.phar";
		passthru("php ".$composerPath." require ".$package);
		
	}
	
}
