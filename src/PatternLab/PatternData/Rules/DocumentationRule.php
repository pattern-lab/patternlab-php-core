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
		list($yaml,$markdown) = Documentation::parse($text);
		
		// grab the title and unset it from the yaml so it doesn't get duped in the meta
		if (isset($yaml["title"])) {
			$title = $yaml["title"];
			unset($yaml["title"]);
		}
		
		// figure out if this is a top level pattern type or pattern subtype
		$patternSubtypeDoc = false;
		$patternTypeDoc = false;
		if ($depth == 0) {
			foreach (glob($patternSourceDir.DIRECTORY_SEPARATOR.$patternType,GLOB_ONLYDIR) as $dir) {
				$dir = str_replace($patternSourceDir.DIRECTORY_SEPARATOR,"",$dir);
				if ($dir == $doc) {
					$patternTypeDoc = true;
					break;
				}
			}
		} else if ($depth == 1){
				// go through all of the directories to see if this one matches our doc
			foreach (glob($patternSourceDir.DIRECTORY_SEPARATOR.$patternType.DIRECTORY_SEPARATOR."*",GLOB_ONLYDIR) as $dir) {
				$dir = str_replace($patternSourceDir.DIRECTORY_SEPARATOR.$patternType.DIRECTORY_SEPARATOR,"",$dir);
				if ($dir == $doc) {
					$patternSubtypeDoc = true;
					break;
				}
			}
		}
		
		$category = "pattern"; // By default, make the pattern type a "pattern"
		$patternStoreKey = $docPartial;

		// Update if patternType or subtype
		if ($patternTypeDoc) {
			$category = "patternType";
			$patternStoreKey = $patternTypeDash."-pltype";
			
			// organisms-pltype
		} else if ($patternSubtypeDoc){
			$category = "patternSubtype";
			$patternStoreKey = $docPartial."-plsubtype";
		}

		$patternStoreData = array("category"   => $category,
								  "desc"       => trim($markdown),
								  "descExists" => true,
								  "meta"       => $yaml,
								  "full"       => $doc);

		// can set `title: My Cool Pattern` instead of lifting from file name
		if (isset($title)) {
			$patternStoreData["nameClean"] = $title;
		}

		$availableKeys = [
      'state', // can use `state: inprogress` instead of `button@inprogress.mustache`
      'hidden', // setting to `true`, removes from menu and viewall, which is same as adding `_` prefix
      'noviewall', // setting to `true`, removes from view alls but keeps in menu, which is same as adding `-` prefix
      'order', // @todo implement order
      'tags', // not implemented, awaiting spec approval and integration with styleguide kit. adding to be in sync with Node version.
      'links', // not implemented, awaiting spec approval and integration with styleguide kit. adding to be in sync with Node version.
    ];

		foreach ($availableKeys as $key) {
      if (isset($yaml[$key])) {
        $patternStoreData[$key] = $yaml[$key];
      }
    }


		
		// if the pattern data store already exists make sure this data overwrites it
		$patternStoreData = (PatternData::checkOption($patternStoreKey)) ? array_replace_recursive(PatternData::getOption($patternStoreKey),$patternStoreData) : $patternStoreData;
		PatternData::setOption($patternStoreKey, $patternStoreData);
		
	}
	
}
