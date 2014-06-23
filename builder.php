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

// autoload plugin vendored items if available
$pluginDir = str_replace("src/PatternLab/../../","",\PatternLab\Config::$options["pluginDir"]);
$plugins = scandir($pluginDir);
foreach ($plugins as $plugin) {
	if (($plugin != ".") && ($plugin != "..") && file_exists($pluginDir."/".$plugin."/vendor/autoload.php")) {
		require ($pluginDir."/".$plugin."/vendor/autoload.php");
	}
}

// initialize the dispatcher & note that the config has been loaded
\PatternLab\Dispatcher::init();
\PatternLab\Dispatcher::$instance->dispatch("config.configLoadEnd");

// run the console
\PatternLab\Console::run();
