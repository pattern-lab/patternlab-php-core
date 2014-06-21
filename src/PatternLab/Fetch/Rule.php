<?php

/*!
 * Fetch Option Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\Fetch;

class Rule {
	
	protected $name;
	protected $unpack;
	protected $writeTo;
	protected $commandOption;
	
	public function __construct() {
		
		// nothing here yet
		
	}
	
	public function test($commandOption) {
		return ($commandOption == $this->commandOption);
	}
	
}
