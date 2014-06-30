<?php

/*!
 * Mustache Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Handles the collection of Mustache helpers so they can be added to the Mustache engine
 *
 */

namespace PatternLab\PatternEngine\Helpers;

class MustacheHelper {
	
	public static $helpers = array();
	
	public static function add($tag,$function) {
		
		self::$helpers[$tag] = $function;
		
	}
	
	public static function get() {
		
		return self::$helpers;
		
	}
	
}