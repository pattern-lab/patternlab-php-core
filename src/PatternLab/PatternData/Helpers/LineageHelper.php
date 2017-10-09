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
	
	protected $lineageMatch;
	protected $lineageMatchKey;
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->lineageMatch    = html_entity_decode(Config::getOption("lineageMatch"),ENT_QUOTES);
		$this->lineageMatchKey = Config::getOption("lineageMatchKey");
		
	}
	
	public function run() {
		
		// set-up default vars
		$foundLineages    = array();
		$patternSourceDir = Config::getOption("patternSourceDir");
		$patternExtension = Config::getOption("patternExtension");
		$suffixRendered   =	Config::getOption("outputFileSuffixes.rendered");
		
		// check for the regular lineages in only normal patterns
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!isset($patternStoreData["pseudo"]))) {
				
				$patternLineages = array();
				$fileData        = isset($patternStoreData["patternRaw"]) ? $patternStoreData["patternRaw"] : "";
				$foundLineages   = $this->findLineages($fileData);
				
				if (!empty($foundLineages)) {
					
					foreach ($foundLineages as $lineage) {
						
					/**
						* Fix for Pattern Lab Lineages when using Twig Namespaces. 
						* Converts the full file path to PL-friendly shorthand so 
						* they are internally registered.
						*
						* 1.  Only handle instances where we aren't or can't use the 
						*     shorthand PL path reference in templates, specifically 
						*     in Twig / D8 when we need to use Twig namespaces in 
						*     our template paths.
						* 2.  Strip off the @ sign at the beginning of our $lineage string.
						* 3.  Break apart the full lineage path based on any slashes that
						*     may exist.
						* 4.  Store the length of our broken up path for reference below
						* 5.  Store the first part of the string up to the first slash "/"
						* 6.  Now grab the last part of the pattern key, based on the length
						*     of the path we previously exploded.
						* 7.  Remove any "_" from pattern Name.
						* 8.  Remove any potential prefixed numbers or number + dash 
						*     combos on our Pattern Name.
						* 9.  Strip off the pattern path extension (.twig, 
						*     .mustache, etc) if it exists.
						* 10. If the pattern name parsed had an extension, 
						*     re-assign our Pattern Name to that.
						* 11. Finally, re-assign $lineage to the default PL pattern key.
						*/

						if ($lineage[0] == '@') {                    /* [1] */
							$lineage = ltrim($lineage, '@');           /* [2] */
							$lineageParts = explode('/', $lineage);    /* [3] */
							$length = count($lineageParts);            /* [4] */
							$patternType = $lineageParts[0];           /* [5] */

							$patternName = $lineageParts[$length - 1]; /* [6] */
							$patternName = ltrim($patternName, '_');   /* [7] */
							$patternName = preg_replace('/^[0-9\-]+/', '', 
							$patternName); /* [8] */

							$patternNameStripped = explode('.' . $patternExtension, $patternName); /* [9] */

							if (count($patternNameStripped) > 1) { /* [10] */
								$patternName = $patternNameStripped[0];
							}
							$lineage = $patternType . "-" . $patternName;	/* [11] */
						}

						if (PatternData::getOption($lineage)) {
							
							$patternLineages[] = array("lineagePattern" => $lineage,
													   "lineagePath"    => "../../patterns/".$patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].$suffixRendered.".html");
							
						} else {
							
							if (strpos($lineage, '/') === false) {
								$fileName = $patternStoreData["pathName"].".".$patternExtension;
								Console::writeWarning("you may have a typo in ".$fileName.". `".$lineage."` is not a valid pattern...");
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
			
			if (($patternStoreData["category"] == "pattern") && (!isset($patternStoreData["pseudo"])) && isset($patternStoreData["partial"])) {
				
				$patternLineagesR = array();
				
				$storeTake2 = PatternData::get();
				foreach ($storeTake2 as $haystackKey => $haystackData) {
					
					if (($haystackData["category"] == "pattern") && (isset($haystackData["lineages"])) && (!empty($haystackData["lineages"]))) {
						
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
																	"lineagePath"    => "../../patterns/".$path."/".$path.$suffixRendered.".html");
																
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
	* @param  {String}       the data from the raw pattern
	*
	* @return {Array}        a list of patterns
	*/
	protected function findLineages($data) {
		if (preg_match_all("/".$this->lineageMatch."/",$data,$matches)) {
			return array_unique($matches[$this->lineageMatchKey]);
		}
		return array();
	}
	
}
