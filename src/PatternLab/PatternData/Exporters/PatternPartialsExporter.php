<?php

/*!
 * Pattern Data Pattern Partials Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generates the partials to be used in the viewall & styleguide
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData;
use \PatternLab\Timer;

class PatternPartialsExporter extends \PatternLab\PatternData\Exporter {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
	}
	
	/**
	* Compare the search and ignore props against the name.
	* Can use && or || in the comparison
	* @param  {String}       the type of the pattern that should be used in the view all
	* @param  {String}       the subtype of the pattern that be used in the view all
	*
	* @return {Array}        the list of partials
	*/
	public function run($type = "", $subtype = "") {
		
		// default vars
		$patternPartials    = array();
		$styleGuideExcludes = Config::getOption("styleGuideExcludes");
		
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && (!$patternStoreData["hidden"]) && (!$patternStoreData["noviewall"]) && ($patternStoreData["depth"] > 1) && (!in_array($patternStoreData["type"],$styleGuideExcludes))) {
				
				if ((($patternStoreData["type"] == $type) && empty($subtype)) || (empty($type) && empty($subtype)) || (($patternStoreData["type"] == $type) && ($patternStoreData["subtype"] == $subtype))) {
					
					$patternPartialData                            = array();
					$patternPartialData["patternName"]             = ucwords($patternStoreData["nameClean"]);
					$patternPartialData["patternLink"]             = $patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html";
					$patternPartialData["patternPartial"]          = $patternStoreData["partial"];
					$patternPartialData["patternPartialCode"]      = $patternStoreData["code"];
					
					$patternPartialData["patternLineageExists"]    = isset($patternStoreData["lineages"]);
					$patternPartialData["patternLineages"]         = isset($patternStoreData["lineages"]) ? $patternStoreData["lineages"] : array();
					$patternPartialData["patternLineageRExists"]   = isset($patternStoreData["lineagesR"]);
					$patternPartialData["patternLineagesR"]        = isset($patternStoreData["lineagesR"]) ? $patternStoreData["lineagesR"] : array();
					$patternPartialData["patternLineageEExists"]   = (isset($patternStoreData["lineages"]) || isset($patternStoreData["lineagesR"]));
					
					$patternPartialData["patternDescExists"]       = isset($patternStoreData["desc"]);
					$patternPartialData["patternDesc"]             = isset($patternStoreData["desc"]) ? $patternStoreData["desc"] : "";
					
					$patternPartialData["patternDescAdditions"]    = isset($patternStoreData["partialViewDescAdditions"]) ? $patternStoreData["partialViewDescAdditions"] : array();
					$patternPartialData["patternExampleAdditions"] = isset($patternStoreData["partialViewExampleAdditions"]) ? $patternStoreData["partialViewExampleAdditions"] : array();
					
					//$patternPartialData["patternCSSExists"]        = Config::$options["enableCSS"];
					$patternPartialData["patternCSSExists"]        = false;
					
					// add the pattern data so it can be exported
					$patternData = array();
					//$patternFooterData["patternFooterData"]["cssEnabled"]      = (Config::$options["enableCSS"] && isset($this->patternCSS[$p])) ? "true" : "false";
					$patternData["cssEnabled"]        = false;
					$patternData["lineage"]           = isset($patternStoreData["lineages"])  ? $patternStoreData["lineages"] : array();
					$patternData["lineageR"]          = isset($patternStoreData["lineagesR"]) ? $patternStoreData["lineagesR"] : array();
					$patternData["patternBreadcrumb"] = $patternStoreData["breadcrumb"];
					$patternData["patternDesc"]       = (isset($patternStoreData["desc"])) ? $patternStoreData["desc"] : "";
					$patternData["patternExtension"]  = ".mustache";
					$patternData["patternName"]       = $patternStoreData["nameClean"];
					$patternData["patternPartial"]    = $patternStoreData["partial"];
					$patternData["patternState"]      = $patternStoreData["state"];
					$patternPartialData["patternData"] = json_encode($patternData);
					
					$patternPartials[]                             = $patternPartialData;
				
				}
				
			}
			
		}
		
		return array("partials" => $patternPartials, "cacheBuster" => Data::getOption("cacheBuster"));
		
	}
	
}
