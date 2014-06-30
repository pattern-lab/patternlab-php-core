<?php

/*!
 * Pattern Engine Fetch Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * How to handle requests for plug-ins
 *
 */

namespace PatternLab\Fetch\Rules;

use \PatternLab\Config;
use \PatternLab\Fetch\Rule;

class PatternEngineRule extends Rule {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->name          = "pattern engine";
		$this->unpack        = false;
		$this->writeDir      = Config::$options["pluginDir"];
		
		$this->longCommand   = "patternengine";
		
	}
	
}