<?php

/*!
 * Pattern Data Documentation Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * If a documentation file (.md) it is parsed and the info added to PatternData::$store
 *
 */

namespace PatternLab\PatternData\Rules;

use \PatternLab\Config;
use \PatternLab\PatternData;
use \PatternLab\Parsers\Documentation;
use \PatternLab\Timer;
use \PatternLab\Data;

class DocumentationRule extends \PatternLab\PatternData\Rule {
	
	public function __construct($options) {
		
		parent::__construct($options);
		
		$this->depthProp  = 3; // 3 means that depth won't be checked
		$this->extProp    = "md";
		$this->isDirProp  = false;
		$this->isFileProp = true;
		$this->searchProp = "";
		$this->ignoreProp = "";
		
	}
	
	public function run($depth, $ext, $path, $pathName, $name) {
		
		// load default vars
		$patternType        = PatternData::getPatternType();
		$patternTypeDash    = PatternData::getPatternTypeDash();
		$dirSep             = PatternData::getDirSep();
		
		// set-up the names, $name == 00-colors.md
		$doc        = str_replace(".".$this->extProp,"",$name);              // 00-colors
		$docDash    = $this->getPatternName(str_replace("_","",$doc),false); // colors
		$docPartial = $patternTypeDash."-".$docDash;
		
		// default vars
		$patternSourceDir = Config::getOption("patternSourceDir");
		
		// parse data
		$text = file_get_contents($patternSourceDir.DIRECTORY_SEPARATOR.$pathName);
		
		// grab the title and unset it from the yaml so it doesn't get duped in the meta
		if (isset($yaml["title"])) {
			$title = $yaml["title"];
			unset($yaml["title"]);
		}
		
		// figure out if this is a pattern subtype
		$patternSubtypeDoc = false;
		if ($depth == 1) {
			// go through all of the directories to see if this one matches our doc
			foreach (glob($patternSourceDir.DIRECTORY_SEPARATOR.$patternType.DIRECTORY_SEPARATOR."*",GLOB_ONLYDIR) as $dir) {
				$dir = str_replace($patternSourceDir.DIRECTORY_SEPARATOR.$patternType.DIRECTORY_SEPARATOR,"",$dir);
				if ($dir == $doc) {
					$patternSubtypeDoc = true;
					break;
				}
			}
			
		}
		
		$category         = ($patternSubtypeDoc) ? "patternSubtype" : "pattern";
		$patternStoreKey  = ($patternSubtypeDoc) ? $docPartial."-plsubtype" : $docPartial;
    
		//Setup the Pattern Loader so we can pre-render template markup used in our markdown files, prior to being rendered in Markdown.
		$patternEngineBasePath = \PatternLab\PatternEngine::getInstance()->getBasePath();
		$patternLoaderClass    = $patternEngineBasePath."\Loaders\PatternLoader";
		$patternLoader         = new $patternLoaderClass($options);
    
		//Setup the default pattern data.  
		$data = Data::getPatternSpecificData($patternStoreKey);
		
		//Render the markdown content as a pattern, piping in the pattern-specific data from above.
		$text                  = $patternLoader->render(array("pattern" => $text, "data" => $data));
		
		//Finally parse the resulting content as normal markup; continue as usual.
		list($yaml,$markdown) = Documentation::parse($text);
		
		$patternStoreData = array("category"   => $category,
								  "desc"       => trim($markdown),
								  "descExists" => true,
								  "meta"       => $yaml,
								  "full"       => $doc);

		if (isset($title)) {
			$patternStoreData["nameClean"] = $title;
		}
		
		// if the pattern data store already exists make sure this data overwrites it
		$patternStoreData = (PatternData::checkOption($patternStoreKey)) ? array_replace_recursive(PatternData::getOption($patternStoreKey),$patternStoreData) : $patternStoreData;
		PatternData::setOption($patternStoreKey, $patternStoreData);
		
	}
	
}
