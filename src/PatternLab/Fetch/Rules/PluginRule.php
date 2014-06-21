<?php

/*!
 * Plug-in Fetch Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * How to handle requests for plug-ins
 *
 */

namespace PatternLab\Fetch\Rules;

use \PatternLab\Fetch\Rule;

class PluginRule extends Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->name          = "plug-in";
		$this->unpack        = false;
		$this->writeDir      = Config::$options["pluginDir"];
		$this->commandOption = "p";
		
	}
	
}