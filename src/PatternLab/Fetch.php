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

class Fetch {
	
	/**
	 * Fetch a package from GitHub
	 * @param  {String}    the path to the package to be downloaded
	 *
	 * @return {String}    the modified file contents
	 */
	public function fetch($package = "") {
		
		if (empty($package)) {
			print "please provide a path for the package before trying to fetch it...\n";
			exit;
		}
		
		$composerPath = __DIR__."/../../bin/composer.phar";
		passthru("php ".$composerPath." require ".$package);
		
	}
	
}