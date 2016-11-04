<?php

/*!
 * Builder Class
 *
 * Copyright (c) 2013-2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Holds most of the "generate" functions used in the the Generator and Watcher class
 *
 */

namespace PatternLab;

use \PatternLab\Annotations;
use \PatternLab\Config;
use \PatternLab\Data;
use \PatternLab\Dispatcher;
use \PatternLab\FileUtil;
use \PatternLab\Parsers\Documentation;
use \PatternLab\PatternData\Exporters\NavItemsExporter;
use \PatternLab\PatternData\Exporters\PatternPartialsExporter;
use \PatternLab\PatternData\Exporters\PatternPathDestsExporter;
use \PatternLab\PatternData\Exporters\PatternPathSrcExporter;
use \PatternLab\PatternData\Exporters\ViewAllPathsExporter;
use \PatternLab\PatternEngine;
use \PatternLab\Template;
use \PatternLab\Timer;
use \Symfony\Component\Finder\Finder;

class Builder {
	
	/**
	* When initializing the Builder class make sure the template helper is set-up
	*/
	public function __construct() {
		
		// set-up the pattern engine
		PatternEngine::init();
		
		// set-up the various attributes for rendering templates
		Template::init();
		
	}
	
	/**
	* Generates the annotations js file
	*/
	protected function generateAnnotations() {
		
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// note the start of the operation
		$dispatcherInstance->dispatch("builder.generateAnnotationsStart");
		
		// default var
		$publicDir = Config::getOption("publicDir");
		
		// encode the content so it can be written out
		$json      = json_encode(Annotations::get());
		
		// make sure annotations/ exists
		if (!is_dir($publicDir."/annotations")) {
			mkdir($publicDir."/annotations");
		}
		
		// write out the new annotations.js file
		file_put_contents($publicDir."/annotations/annotations.js","var comments = ".$json.";");
		
		// note the end of the operation
		$dispatcherInstance->dispatch("builder.generateAnnotationsEnd");
		
	}
	
	/**
	* Generates the data that powers the index page
	*/
	protected function generateIndex() {
		
		// bomb if missing index.html
		if (!file_exists(Config::getOption("publicDir")."/index.html")) {
			$index = Console::getHumanReadablePath(Config::getOption("publicDir")).DIRECTORY_SEPARATOR."index.html";
			Console::writeError("<path>".$index."</path> is missing. grab a copy from your StyleguideKit...");
		}
		
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// note the start of the operation
		$dispatcherInstance->dispatch("builder.generateIndexStart");
		
		// default var
		$dataDir = Config::getOption("publicDir")."/styleguide/data";
		
		// double-check that the data directory exists
		if (!is_dir($dataDir)) {
			FileUtil::makeDir($dataDir);
		}
		
		$output = "";
		
		// load and write out the config options
		$config                         = array();
		$exposedOptions                 = Config::getOption("exposedOptions");
		foreach ($exposedOptions as $exposedOption) {
			$config[$exposedOption]     = Config::getOption($exposedOption);
		}
		$output     .= "var config = ".json_encode($config).";\n";
		
		// load the ish Controls
		$ishControls     = array();
		$controlsToHide  = array();
		$ishControlsHide = Config::getOption("ishControlsHide");
		if ($ishControlsHide) {
			foreach ($ishControlsHide as $controlToHide) {
				$controlsToHide[$controlToHide] = "true";
			}
		}
		$ishControls["ishControlsHide"] = $controlsToHide;
		$output      .= "var ishControls = ".json_encode($ishControls).";\n";
		
		// load and write out the items for the navigation
		$niExporter   = new NavItemsExporter();
		$navItems     = $niExporter->run();
		$output      .= "var navItems = ".json_encode($navItems).";\n";
		
		// load and write out the items for the pattern paths
		$patternPaths = array();
		$ppdExporter  = new PatternPathDestsExporter();
		$patternPaths = $ppdExporter->run();
		$output      .= "var patternPaths = ".json_encode($patternPaths).";\n";
		
		// load and write out the items for the view all paths
		$viewAllPaths = array();
		$vapExporter  = new ViewAllPathsExporter();
		$viewAllPaths = $vapExporter->run($navItems);
		$output      .= "var viewAllPaths = ".json_encode($viewAllPaths).";\n";
		
		// gather plugin package information
		$packagesInfo = array();
		$componentDir = Config::getOption("componentDir");
		if (!is_dir($componentDir)) {
			mkdir($componentDir);
		}
		$componentPackagesDir = $componentDir."/packages";
		if (!is_dir($componentDir."/packages")) {
			mkdir($componentDir."/packages");
		}
		$finder = new Finder();
		$finder->files()->name("*.json")->in($componentPackagesDir);
		$finder->sortByName();
		foreach ($finder as $file) {
			$filename = $file->getFilename();
			if ($filename[0] != "_") {
				$javascriptPaths = array();
				$packageInfo = json_decode(file_get_contents($file->getPathname()),true);
				foreach ($packageInfo["templates"] as $templateKey => $templatePath) {
					$templatePathFull = $componentDir."/".$packageInfo["name"]."/".$templatePath;
					$packageInfo["templates"][$templateKey] = (file_exists($templatePathFull)) ? file_get_contents($templatePathFull) : "";
				}
				foreach ($packageInfo["javascripts"] as $key => $javascriptPath) {
					$javascriptPaths[] = "patternlab-components/".$packageInfo["name"]."/".$javascriptPath;
				}
				$packageInfo["javascripts"] = $javascriptPaths;
				$packagesInfo[] = $packageInfo;
			}
		}
		$output .= "var plugins = ".json_encode($packagesInfo).";";
		
		// write out the data
		file_put_contents($dataDir."/patternlab-data.js",$output);
		
		// note the end of the operation
		$dispatcherInstance->dispatch("builder.generateIndexEnd");
		
	}
	
	/**
	* Generates all of the patterns and puts them in the public directory
	* @param   {Array}     various options that might affect the export. primarily the location.
	*/
	protected function generatePatterns($options = array()) {
		
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// note the beginning of the operation
		$dispatcherInstance->dispatch("builder.generatePatternsStart");
		
		// set-up common vars
		$exportFiles      = (isset($options["exportFiles"]) && $options["exportFiles"]);
		$exportDir        = Config::getOption("exportDir");
		$patternPublicDir = !$exportFiles ? Config::getOption("patternPublicDir") : Config::getOption("patternExportDir");
		$patternSourceDir = Config::getOption("patternSourceDir");
		$patternExtension = Config::getOption("patternExtension");
		$suffixRendered   =	Config::getOption("outputFileSuffixes.rendered");
		$suffixRaw        = Config::getOption("outputFileSuffixes.rawTemplate");
		$suffixMarkupOnly = Config::getOption("outputFileSuffixes.markupOnly");
		
		// make sure the export dir exists
		if ($exportFiles && !is_dir($exportDir)) {
			mkdir($exportDir);
		}
		
		// make sure patterns exists
		if (!is_dir($patternPublicDir)) {
			mkdir($patternPublicDir);
		}
		
		// loop over the pattern data store to render the individual patterns
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if (($patternStoreData["category"] == "pattern") && isset($patternStoreData["hidden"]) && (!$patternStoreData["hidden"])) {
				
				$path          = $patternStoreData["pathDash"];
				$pathName      = (isset($patternStoreData["pseudo"])) ? $patternStoreData["pathOrig"] : $patternStoreData["pathName"];
				
				// modify the pattern mark-up
				$markup        = $patternStoreData["code"];
				$markupFull    = $patternStoreData["header"].$markup.$patternStoreData["footer"];
				$markupEngine  = file_get_contents($patternSourceDir."/".$pathName.".".$patternExtension);
				
				// if the pattern directory doesn't exist create it
				if (!is_dir($patternPublicDir."/".$path)) {
					mkdir($patternPublicDir."/".$path);
				}
				
				// write out the various pattern files
				file_put_contents($patternPublicDir."/".$path."/".$path.$suffixRendered.".html",$markupFull);
				if (!$exportFiles) {
					file_put_contents($patternPublicDir."/".$path."/".$path.$suffixMarkupOnly.".html",$markup);
					file_put_contents($patternPublicDir."/".$path."/".$path.$suffixRaw.".".$patternExtension,$markupEngine);
				}
				
			}
			
		}
		
		// note the end of the operation
		$dispatcherInstance->dispatch("builder.generatePatternsEnd");
		
	}
	
	/**
	* Generates the style guide view
	*/
	protected function generateStyleguide() {
		
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// note the beginning of the operation
		$dispatcherInstance->dispatch("builder.generateStyleguideStart");
		
		// default var
		$publicDir = Config::getOption("publicDir");
		
		// load the pattern loader
		$ppdExporter             = new PatternPathSrcExporter();
		$patternPathSrc          = $ppdExporter->run();
		$options                 = array();
		$options["patternPaths"] = $patternPathSrc;
		$patternEngineBasePath   = PatternEngine::getInstance()->getBasePath();
		$patternLoaderClass      = $patternEngineBasePath."\Loaders\PatternLoader";
		$patternLoader           = new $patternLoaderClass($options);
		
		// check directories i need
		if (!is_dir($publicDir."/styleguide/")) {
			mkdir($publicDir."/styleguide/");
		}
		
		if (!is_dir($publicDir."/styleguide/html/")) {
			mkdir($publicDir."/styleguide/html/");
		}
			
		// grab the partials into a data object for the style guide
		$ppExporter                   = new PatternPartialsExporter();
		$partials                     = $ppExporter->run();
		
		// add the pattern data so it can be exported
		$patternData = array();
		
		// add the pattern lab specific mark-up
		$filesystemLoader             = Template::getFilesystemLoader();
		$stringLoader                 = Template::getStringLoader();
		
		$globalData                   = Data::get();
		$globalData["patternLabHead"] = $stringLoader->render(array("string" => Template::getHTMLHead(), "data" => array("cacheBuster" => $partials["cacheBuster"])));
		$globalData["patternLabFoot"] = $stringLoader->render(array("string" => Template::getHTMLFoot(), "data" => array("cacheBuster" => $partials["cacheBuster"], "patternData" => json_encode($patternData))));
		$globalData["viewall"]        = true;
		
		$header                       = $patternLoader->render(array("pattern" => Template::getPatternHead(), "data" => $globalData));
		$code                         = $filesystemLoader->render(array("template" => "viewall", "data" => $partials));
		$footer                       = $patternLoader->render(array("pattern" => Template::getPatternFoot(), "data" => $globalData));
		
		$styleGuidePage               = $header.$code.$footer;
		
		file_put_contents($publicDir."/styleguide/html/styleguide.html",$styleGuidePage);
		
		// note the end of the operation
		$dispatcherInstance->dispatch("builder.generateStyleguideEnd");
		
	}
	
	/**
	* Generates the view all pages
	*/
	protected function generateViewAllPages() {
		
		// set-up the dispatcher
		$dispatcherInstance = Dispatcher::getInstance();
		
		// note the beginning of the operation
		$dispatcherInstance->dispatch("builder.generateViewAllPagesStart");
		
		// default vars
		$patternPublicDir = Config::getOption("patternPublicDir");
		$htmlHead         = Template::getHTMLHead();
		$htmlFoot         = Template::getHTMLFoot();
		$patternHead      = Template::getPatternHead();
		$patternFoot      = Template::getPatternFoot();
		$filesystemLoader = Template::getFilesystemLoader();
		$stringLoader     = Template::getStringLoader();
		$globalData       = Data::get();
		
		// load the pattern loader
		$ppdExporter             = new PatternPathSrcExporter();
		$patternPathSrc          = $ppdExporter->run();
		$options                 = array();
		$options["patternPaths"] = $patternPathSrc;
		$patternEngineBasePath   = PatternEngine::getInstance()->getBasePath();
		$patternLoaderClass      = $patternEngineBasePath."\Loaders\PatternLoader";
		$patternLoader           = new $patternLoaderClass($options);
		
		// make sure view all is set
		$globalData["viewall"] = true;
		
		// make sure the pattern dir exists
		if (!is_dir($patternPublicDir)) {
			mkdir($patternPublicDir);
		}
		
		// add view all to each list
		$store = PatternData::get();
		foreach ($store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "patternSubtype") {
				
				// grab the partials into a data object for the style guide
				$ppExporter  = new PatternPartialsExporter();
				$partials    = $ppExporter->run($patternStoreData["type"],$patternStoreData["name"]);
				
				if (!empty($partials["partials"])) {
					
					// add the pattern data so it can be exported
					$patternData = array();
					$patternData["patternPartial"] = "viewall-".$patternStoreData["typeDash"]."-".$patternStoreData["nameDash"];
					
					$globalData["patternLabHead"] = $stringLoader->render(array("string" => Template::getHTMLHead(), "data" => array("cacheBuster" => $partials["cacheBuster"])));
					$globalData["patternLabFoot"] = $stringLoader->render(array("string" => Template::getHTMLFoot(), "data" => array("cacheBuster" => $partials["cacheBuster"], "patternData" => json_encode($patternData))));
					
					// render the parts and join them
					$header      = $patternLoader->render(array("pattern" => $patternHead, "data" => $globalData));
					$code        = $filesystemLoader->render(array("template" => "viewall", "data" => $partials));
					$footer      = $patternLoader->render(array("pattern" => $patternFoot, "data" => $globalData));
					$viewAllPage = $header.$code.$footer;
					
					// if the pattern directory doesn't exist create it
					$patternPath = $patternStoreData["pathDash"];
					if (!is_dir($patternPublicDir."/".$patternPath)) {
						mkdir($patternPublicDir."/".$patternPath);
						file_put_contents($patternPublicDir."/".$patternPath."/index.html",$viewAllPage);
					} else {
						file_put_contents($patternPublicDir."/".$patternPath."/index.html",$viewAllPage);
					}
					
				}
				
			} else if (($patternStoreData["category"] == "patternType") && PatternData::hasPatternSubtype($patternStoreData["nameDash"])) {
				
				// grab the partials into a data object for the style guide
				$ppExporter  = new PatternPartialsExporter();
				$partials    = $ppExporter->run($patternStoreData["name"]);
				
				if (!empty($partials["partials"])) {
					
					// add the pattern data so it can be exported
					$patternData = array();
					$patternData["patternPartial"] = "viewall-".$patternStoreData["nameDash"]."-all";
					
					// add the pattern lab specific mark-up
					$partials["patternLabHead"] = $stringLoader->render(array("string" => $htmlHead, "data" => array("cacheBuster" => $partials["cacheBuster"])));
					$partials["patternLabFoot"] = $stringLoader->render(array("string" => $htmlFoot, "data" => array("cacheBuster" => $partials["cacheBuster"], "patternData" => json_encode($patternData))));
					
					// render the parts and join them
					$header      = $patternLoader->render(array("pattern" => $patternHead, "data" => $globalData));
					$code        = $filesystemLoader->render(array("template" => "viewall", "data" => $partials));
					$footer      = $patternLoader->render(array("pattern" => $patternFoot, "data" => $globalData));
					$viewAllPage = $header.$code.$footer;
					
					// if the pattern directory doesn't exist create it
					$patternPath = $patternStoreData["pathDash"];
					if (!is_dir($patternPublicDir."/".$patternPath)) {
						mkdir($patternPublicDir."/".$patternPath);
						file_put_contents($patternPublicDir."/".$patternPath."/index.html",$viewAllPage);
					} else {
						file_put_contents($patternPublicDir."/".$patternPath."/index.html",$viewAllPage);
					}
					
				}
				
			}
			
		}
		
		// note the end of the operation
		$dispatcherInstance->dispatch("builder.generateViewAllPagesEnd");
		
	}
	
}
