<?php

/*!
 * File Util Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generic file related functions that are used throughout Pattern Lab
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Console;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class FileUtil {

	/**
	* Copies a file from the given source path to the given public path.
	* THIS IS NOT FOR PATTERNS 
	* @param  {String}       the source file
	* @param  {String}       the public file
	*/
	protected static function moveFile($s,$p) {
		if (file_exists(Config::$options["sourceDir"]."/".$s)) {
			copy(Config::$options["sourceDir"]."/".$s,Config::$options["publicDir"]."/".$p);
		}
	}

	/**
	* Moves static files that aren't directly related to Pattern Lab
	* @param  {String}       file name to be moved
	* @param  {String}       copy for the message to be printed out
	* @param  {String}       part of the file name to be found for replacement
	* @param  {String}       the replacement
	*/
	public static function moveStaticFile($fileName,$copy = "", $find = "", $replace = "") {
		self::moveFile($fileName,str_replace($find, $replace, $fileName));
		Util::updateChangeTime();
		if ($copy != "") {
			Console::writeLine($fileName." ".$copy."...");
		}
	}

	/**
	* Check to see if a given filename is in a directory that should be ignored
	* @param  {String}       file name to be checked
	*
	* @return {Boolean}      whether the directory should be ignored
	*/
	public static function ignoreDir($fileName) {
		$id = Config::$options["id"];
		foreach ($id as $dir) {
			$pos = strpos(DIRECTORY_SEPARATOR.$fileName,DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR);
			if ($pos !== false) {
				return true;
			}
		}
		return false;
	}
	
	/**
	* Taken from Composer: https://github.com/composer/composer/blob/master/src/Composer/Util/Filesystem.php
	*
	* Normalize a path. This replaces backslashes with slashes, removes ending
	* slash and collapses redundant separators and up-level references.
	*
	* @param  string $path Path to the file or directory
	* @return string
	*/
	public static function normalizePath($path) {
		$parts = array();
		$path = strtr($path, '\\', '/');
		$prefix = '';
		$absolute = false;
		
		if (preg_match('{^([0-9a-z]+:(?://(?:[a-z]:)?)?)}i', $path, $match)) {
			$prefix = $match[1];
			$path = substr($path, strlen($prefix));
		}
		
		if (substr($path, 0, 1) === '/') {
			$absolute = true;
			$path = substr($path, 1);
		}
		
		$up = false;
		foreach (explode('/', $path) as $chunk) {
			if ('..' === $chunk && ($absolute || $up)) {
				array_pop($parts);
				$up = !(empty($parts) || '..' === end($parts));
			} elseif ('.' !== $chunk && '' !== $chunk) {
				$parts[] = $chunk;
				$up = '..' !== $chunk;
			}
		}
		
		return $prefix.($absolute ? '/' : '').implode('/', $parts);
		
	}
	
	/**
	* Delete everything in export/
	*/
	public static function cleanExport() {
		
		if (is_dir(Config::$options["exportDir"])) {
			
			$files = scandir(Config::$options["exportDir"]);
			foreach ($files as $file) {
				if (($file == "..") || ($file == ".")) {
					array_shift($files);
				} else {
					$key = array_keys($files,$file);
					$files[$key[0]] = Config::$options["exportDir"].DIRECTORY_SEPARATOR.$file;
				}
			}
			
			$fs = new Filesystem();
			$fs->remove($files);
			
		}
		
	}
	
	/**
	* Delete patterns and user-created directories and files in public/
	*/
	public static function cleanPublic() {
		
		// make sure patterns exists before trying to clean it
		if (is_dir(Config::$options["patternPublicDir"])) {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(Config::$options["patternPublicDir"]), \RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
			// for each file figure out what to do with it
			foreach($objects as $name => $object) {
				
				if ($object->isDir()) {
					// if this is a directory remove it
					rmdir($name);
				} else if ($object->isFile() && ($object->getFilename() != "README")) {
					// if this is a file remove it
					unlink($name);
				}
				
			}
			
		}
		
		// scan source/ & public/ to figure out what directories might need to be cleaned up
		$publicDir  = Config::$options["publicDir"];
		$sourceDir  = Config::$options["sourceDir"];
		$publicDirs = glob($publicDir."/*",GLOB_ONLYDIR);
		$sourceDirs = glob(Config::$options["sourceDir"]."/*",GLOB_ONLYDIR);
		
		// make sure some directories aren't deleted
		$ignoreDirs = array("styleguide","snapshots");
		foreach ($ignoreDirs as $ignoreDir) {
			$key = array_search($publicDir."/".$ignoreDir,$publicDirs);
			if ($key !== false){
				unset($publicDirs[$key]);
			}
		}
		
		// compare source dirs against public. remove those dirs w/ an underscore in source/ from the public/ list
		foreach ($sourceDirs as $dir) {
			$cleanDir = str_replace($sourceDir."/","",$dir);
			if ($cleanDir[0] == "_") {
				$key = array_search($publicDir."/".str_replace("_","",$cleanDir),$publicDirs);
				if ($key !== false){
					unset($publicDirs[$key]);
				}
			}
		}
		
		// for the remaining dirs in public delete them and their files
		foreach ($publicDirs as $dir) {
			
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir), \RecursiveIteratorIterator::CHILD_FIRST);
			
			// make sure dots are skipped
			$objects->setFlags(\FilesystemIterator::SKIP_DOTS);
			
			foreach($objects as $name => $object) {
				
				if ($object->isDir()) {
					rmdir($name);
				} else if ($object->isFile()) {
					unlink($name);
				}
				
			}
			
			rmdir($dir);
			
		}
		
	}
	
	/**
	* moves user-generated static files from public/ to export/
	*/
	public static function exportStatic() {
		
		if (!is_dir(Config::$options["exportDir"])) {
			mkdir(Config::$options["exportDir"]);
		}
		
		if (is_dir(Config::$options["publicDir"])) {
			
			// decide which files in public should def. be ignored
			$ignore = array("annotations","data","patterns","styleguide","index.html","latest-change.txt",".DS_Store",".svn","README");
			
			$files = scandir(Config::$options["publicDir"]);
			foreach ($files as $key => $file) {
				if (($file == "..") || ($file == ".")) {
					unset($files[$key]);
				} else if (in_array($file,$ignore)) {
					unset($files[$key]);
				} else if (is_dir(Config::$options["publicDir"].DIRECTORY_SEPARATOR.$file) && !is_dir(Config::$options["sourceDir"].DIRECTORY_SEPARATOR.$file)) {
					unset($files[$key]);
				} else if (is_file(Config::$options["publicDir"].DIRECTORY_SEPARATOR.$file) && !is_file(Config::$options["sourceDir"].DIRECTORY_SEPARATOR.$file)) {
					unset($files[$key]);
				}
			}
			
			$fs = new Filesystem();
			foreach ($files as $file) {
				if (is_dir(Config::$options["publicDir"].DIRECTORY_SEPARATOR.$file)) {
					$fs->mirror(Config::$options["publicDir"].DIRECTORY_SEPARATOR.$file,Config::$options["exportDir"].DIRECTORY_SEPARATOR.$file);
				} else if (is_file(Config::$options["publicDir"].DIRECTORY_SEPARATOR.$file)) {
					$fs->copy(Config::$options["publicDir"].DIRECTORY_SEPARATOR.$file,Config::$options["exportDir"].DIRECTORY_SEPARATOR.$file);
				}
			}
			
		}
		
	}
	
}
