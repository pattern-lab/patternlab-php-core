<?php

/*!
 * Pattern Lab Builder CLI - v0.7.12
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

// check to see if json_decode exists. might be disabled in installs of PHP 5.5
if (!function_exists("json_decode")) {
	print "Please check that your version of PHP includes the JSON extension. It's required for Pattern Lab to run. Aborting.\n";
	exit;
}

// auto-load classes
require(__DIR__."/vendor/autoload.php");

// load the options
\PatternLab\Config::init();

// autoload plugins if available
$pluginDir = str_replace("src/PatternLab/../../","",\PatternLab\Config::$options["pluginDir"]);
if (file_exists($pluginDir."/vendor/autoload.php")) {
	require($pluginDir."/vendor/autoload.php");
}

// initialize the dispatcher
\PatternLab\Dispatcher::init();

// run the console
\PatternLab\Console::run();
