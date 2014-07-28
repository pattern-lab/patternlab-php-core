<?php

/*!
 * Timer Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Records how long it takes for Pattern Lab to run as well as how much memory it used
 *
 */

namespace PatternLab;

use \PatternLab\Console;

class Timer {
	
	protected static $starttime;
	
	/**
	* Start the timer
	*/
	public static function start() {
		
		$mtime = microtime();
		$mtime = explode(" ",$mtime); 
		$mtime = $mtime[1] + $mtime[0]; 
		self::$starttime = $mtime;
		
	}
	
	/**
	* Stop the timer
	*/
	public static function stop() {
		
		if (empty(self::$starttime)) {
			Console::writeLine("<warning>the timer wasn't started...</warning>");
			exit;
		}
		
		$mtime     = microtime();
		$mtime     = explode(" ",$mtime);
		$mtime     = $mtime[1] + $mtime[0];
		$endtime   = $mtime;
		$totaltime = ($endtime - self::$starttime);
		$mem = round((memory_get_peak_usage(true)/1024)/1024,2);
		
		$timeTag = "info";
		if ($totaltime > 0.5) {
			$timeTag = "error";
		} else if ($totaltime > 0.3) {
			$timeTag = "warning";
		}
		
		Console::writeLine("site generation took <".$timeTag.">".$totaltime."</".$timeTag."> seconds and used <info>".$mem."MB</info> of memory...");
		
	}
	
}