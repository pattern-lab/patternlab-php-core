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
use \PatternLab\Template;
use \PatternLab\Timer;

class PatternCodeHelper extends \PatternLab\PatternData\Helper {
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->exportFiles  = $options["exportFiles"];
		$this->exportClean  = $options["exportClean"];
		$this->patternPaths = $options["patternPaths"];
		
	}
	
	public function run() {
		
		// default vars
		$options                 = array();
		$options["patternPaths"] = $this->patternPaths;
		$patternExtension        = Config::getOption("patternExtension");
		$htmlHead                = Template::getHTMLHead();
		$htmlFoot                = Template::getHTMLFoot();
		$patternHead             = Template::getPatternHead();
		$patternFoot             = Template::getPatternFoot();
		
		// load the pattern loader
		Template::setPatternLoader(PatternEngine::$instance->getPatternLoader($options));
		
		foreach (PatternData::$store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && !$patternStoreData["hidden"]) {
				
				$data = Data::getPatternSpecificData($patternStoreKey);
				
				// add the pattern data so it can be exported
				$patternData = array();
				//$patternFooterData["patternFooterData"]["cssEnabled"]      = (Config::$options["enableCSS"] && isset($this->patternCSS[$p])) ? "true" : "false";
				$patternData["cssEnabled"]        = false;
				$patternData["lineage"]           = isset($patternStoreData["lineages"])  ? $patternStoreData["lineages"] : array();
				$patternData["lineageR"]          = isset($patternStoreData["lineagesR"]) ? $patternStoreData["lineagesR"] : array();
				$patternData["patternBreadcrumb"] = $patternStoreData["breadcrumb"];
				$patternData["patternDesc"]       = (isset($patternStoreData["desc"])) ? $patternStoreData["desc"] : "";
				$patternData["patternExtension"]  = $patternExtension;
				$patternData["patternName"]       = $patternStoreData["nameClean"];
				$patternData["patternPartial"]    = $patternStoreData["partial"];
				$patternData["patternState"]      = $patternStoreData["state"];
				
				// extra copy for the code view
				$patternData["patternDescAdditions"] = isset($patternStoreData["codeViewDescAdditions"]) ? $patternStoreData["codeViewDescAdditions"] : array();
				
				// add the pattern lab specific mark-up
				// set a default var
				$exportClean = (isset($options["exportClean"])) ? $options["exportClean"] : false;
				$data["patternLabHead"]           = (!$this->exportFiles) ? Render::Header($htmlHead,array("cacheBuster" => $data["cacheBuster"])) : "";
				$data["patternLabFoot"]           = (!$this->exportFiles) ? Render::Footer($htmlFoot,array("cacheBuster" => $data["cacheBuster"], "patternData" => json_encode($patternData))) : "";
				
				// figure out the source path for the pattern to render
				$srcPath = (isset($patternStoreData["pseudo"])) ? PatternData::$store[$patternStoreData["original"]]["pathName"] : $patternStoreData["pathName"];
				
				$header  = (!$this->exportClean) ? Render::Header($patternHead,$data) : "";
				$code    = Render::Pattern($srcPath,$data);
				$footer  = (!$this->exportClean) ? Render::Footer($patternFoot,$data) : "";
				
				PatternData::$store[$patternStoreKey]["header"] = $header;
				PatternData::$store[$patternStoreKey]["code"]   = $code;
				PatternData::$store[$patternStoreKey]["footer"] = $footer;
				
			}
			
		}
		
	}
	
}
