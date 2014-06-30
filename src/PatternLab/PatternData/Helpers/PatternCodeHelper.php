<?php

/*!
 * Pattern Data Pattern Code Helper Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Renders patterns and stores the rendered code in PatternData::$store
 *
 */

namespace PatternLab\PatternData\Helpers;

use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\PatternData;
use \PatternLab\PatternEngine;
use \PatternLab\Render;
use \PatternLab\Template\Helper;

class PatternCodeHelper extends \PatternLab\PatternData\Helper {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->patternPaths = $options["patternPaths"];
		
	}
	
	public function run() {
		
		$options                 = array();
		$options["patternPaths"] = $this->patternPaths;
		PatternEngine::setup($options);
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && !$patternStoreData["hidden"]) {
				
				$data = Data::getPatternSpecificData($patternStoreKey);
				
				// add the pattern data so it can be exported
				$patternData = array();
				//$patternFooterData["patternFooterData"]["cssEnabled"]      = (Config::$options["enableCSS"] && isset($this->patternCSS[$p])) ? "true" : "false";
				$patternData["cssEnabled"] = false;
				$patternData["lineage"]           = isset($patternStoreData["lineages"])  ? json_encode($patternStoreData["lineages"]) : "[]";
				$patternData["lineageR"]          = isset($patternStoreData["lineagesR"]) ? json_encode($patternStoreData["lineagesR"]) : "[]";
				$patternData["patternBreadcrumb"] = $patternStoreData["breadcrumb"];
				$patternData["patternDesc"]       = (isset($patternStoreData["desc"])) ? $patternStoreData["desc"] : "";
				$patternData["patternExtension"]  = Config::$options["patternExtension"];
				$patternData["patternName"]       = $patternStoreData["nameClean"];
				$patternData["patternPartial"]    = $patternStoreData["partial"];
				$patternData["patternState"]      = $patternStoreData["state"];
				
				// extra copy for the code view
				$patternData["patternDescAdditions"] = isset($patternStoreData["codeViewDescAdditions"]) ? $patternStoreData["codeViewDescAdditions"] : array();
				
				// add the pattern lab specific mark-up
				$data["patternLabHead"]           = Render::Header(Helper::$htmlHead,array("cacheBuster" => $data["cacheBuster"]));
				$data["patternLabFoot"]           = Render::Footer(Helper::$htmlFoot,array("cacheBuster" => $data["cacheBuster"], "patternData" => json_encode($patternData)));
				
				// figure out the source path for the pattern to render
				$srcPath = (isset($patternStoreData["pseudo"])) ? PatternData::$store[$patternStoreData["original"]]["pathName"] : $patternStoreData["pathName"];
				
				$header  = Render::Header(Helper::$patternHead,$data);
				$code    = Render::Pattern($srcPath,$data);
				$footer  = Render::Footer(Helper::$patternFoot,$data);
				
				PatternData::$store[$patternStoreKey]["header"] = $header;
				PatternData::$store[$patternStoreKey]["code"]   = $code;
				PatternData::$store[$patternStoreKey]["footer"] = $footer;
				
			}
			
		}
		
	}
	
}
