<?php

/*!
 * Pattern Engine Mustache Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If the test matches "mustache" it will return an instance of the Mustache Pattern Engine
 *
 */


namespace PatternLab\PatternEngine\Rules;

use \PatternLab\Config;
use \PatternLab\Dispatcher;
use \PatternLab\PatternEngine\Loaders\MustacheLoader;
use \PatternLab\PatternEngine\Helpers\MustacheHelper;

class MustacheRule extends \PatternLab\PatternEngine\Rule {
	
	protected $helpers = array();
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->engineProp = "mustache";
		
	}
		
	public function getInstance($options) {
		
		Dispatcher::$instance->dispatch("mustacheRule.gatherHelpers");
		
		$options["loader"]         = new MustacheLoader(Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		$options["partial_loader"] = new MustacheLoader(Config::$options["patternSourceDir"],array("patternPaths" => $options["patternPaths"]));
		$options["helpers"]        = MustacheHelper::get();
		
		return new \Mustache_Engine($options);
		
	}
	
}
