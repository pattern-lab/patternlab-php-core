<?php

/*!
 * Styleguide Kit Fetch Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * How to handle requests for a starter kit
 *
 */

namespace PatternLab\Fetch\Rules;

use \PatternLab\Config;
use \PatternLab\Fetch\Rule;

class StyleguideKitRule extends Rule {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->name          = "styleguide kit";
		$this->unpack        = false;
		$this->writeDir      = Config::$options["pluginDir"];
		
		$this->shortCommand  = "k";
		$this->longCommand   = "styleguidekit";
		
	}
	
}