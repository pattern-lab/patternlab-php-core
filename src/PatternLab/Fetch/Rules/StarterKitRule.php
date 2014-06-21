<?php

/*!
 * Starter Kit Fetch Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * How to handle requests for a starter kit
 *
 */

namespace PatternLab\Fetch\Rules;

use \PatternLab\Fetch\Rule;

class StarterKitRule extends Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->name          = "starter kit";
		$this->unpack        = true;
		$this->writeDir      = Config::$options["sourceDir"];
		$this->commandOption = "s";
		
	}
	
}