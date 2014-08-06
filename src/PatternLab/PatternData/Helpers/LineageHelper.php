<?php

/*!
 * Pattern Data Lineage Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Finds and adds lineage information to the PatternData::$store
 *
 */

namespace PatternLab\PatternData\Helpers;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\PatternData;
use \PatternLab\Timer;

class LineageHelper extends \PatternLab\PatternData\Helper {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	public function run() {
		
		// set-up default vars
		$foundLineages    = array();
		$patternSourceDir = Config::getOption("patternSourceDir");
		$patternExtension = Config::getOption("patternExtension");
		
		// check for the regular lineages in only normal patterns
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!isset($patternStoreData["pseudo"]))) {
				
				$patternLineages = array();
				$fileName        = $patternStoreData["pathName"].".".$patternExtension;
				$fileNameFull    = $patternSourceDir."/".$fileName;
				
				if (file_exists($fileNameFull)) {
					$foundLineages = $this->findLineages($fileNameFull);
				}
				
				if (!empty($foundLineages)) {
					
					foreach ($foundLineages as $lineage) {
						
						if (PatternData::getOption($lineage)) {
							
							$patternLineages[] = array("lineagePattern" => $lineage,
													   "lineagePath"    => "../../patterns/".$patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html");
							
						} else {
							
							if (strpos($lineage, '/') === false) {
								Console::writeWarning("you may have a typo in ".$fileName.". {{> ".$lineage." }} is not a valid pattern...");
							}
							
						}
						
					}
					
					// add the lineages to the PatternData::$store
					PatternData::setPatternOption($patternStoreKey,"lineages",$patternLineages);
					
				}
				
			}
			
		}
		
		// handle all of those pseudo patterns
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (isset($patternStoreData["pseudo"]))) {
				
				// add the lineages to the PatternData::$store
				$patternStoreKeyOriginal = $patternStoreData["original"];
				PatternData::setPatternOption($patternStoreKey,"lineages",PatternData::getPatternOption($patternStoreKeyOriginal,"lineages"));
				
			}
			
		}
		
		// check for the reverse lineages and skip pseudo patterns
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!isset($patternStoreData["pseudo"]))) {
				
				$patternLineagesR = array();
				
				$storeTake2 = PatternData::get();
				foreach ($storeTake2 as $haystackKey => $haystackData) {
					
					if (($haystackData["category"] == "pattern") && (isset($haystackData["lineages"]))) {
						
						foreach ($haystackData["lineages"] as $haystackLineage) {
							
							if ($haystackLineage["lineagePattern"] == $patternStoreData["partial"]) {
								
								$foundAlready = false;
								foreach ($patternLineagesR as $patternCheck) {
									
									if ($patternCheck["lineagePattern"] == $patternStoreData["partial"]) {
										$foundAlready = true;
										break;
									}
								
								}
							
								if (!$foundAlready) {
									
									if (PatternData::getOption($haystackKey)) {
										
										$path = PatternData::getPatternOption($haystackKey,"pathDash");
										$patternLineagesR[] = array("lineagePattern" => $haystackKey, 
																	"lineagePath"    => "../../patterns/".$path."/".$path.".html");
																
									}
								
								}
							
							}
						
						}
						
					}
					
				}
				
				PatternData::setPatternOption($patternStoreKey,"lineagesR",$patternLineagesR);
				
			}
			
		}
		
		// handle all of those pseudo patterns
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (isset($patternStoreData["pseudo"]))) {
				
				// add the lineages to the PatternData::$store
				$patternStoreKeyOriginal = $patternStoreData["original"];
				PatternData::setPatternOption($patternStoreKey,"lineagesR",PatternData::getPatternOption($patternStoreKeyOriginal,"lineagesR"));
				
			}
			
		}
		
	}
	
	
	/**
	* Get the lineage for a given pattern by parsing it and matching mustache partials
	* @param  {String}       the filename for the pattern to be parsed
	*
	* @return {Array}        a list of patterns
	*/
	protected function findLineages($filename) {
		$data = file_get_contents($filename);
		if (preg_match_all('/{{>([ ]+)?([A-Za-z0-9-_]+)(?:\:[A-Za-z0-9-]+)?(?:(| )\(.*)?([ ]+)?}}/',$data,$matches)) {
			return array_unique($matches[2]);
		}
		return array();
	}
	
}