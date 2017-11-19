<?php

/*!
 * Pattern Data Nav Items Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Generates the array of navigation data for Pattern Lab
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Config;
use \PatternLab\PatternData;
use \PatternLab\Timer;

class NavItemsExporter extends \PatternLab\PatternData\Exporter {
	
	protected $store;
	protected $styleGuideExcludes;
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->store = PatternData::get();
		$this->styleGuideExcludes = Config::getOption("styleGuideExcludes");
	}


	// Sort navigation level based on the `order` key (if it exists)
	private function sortNavByOrder($a, $b) {
		if (!isset($a['order'])){
			return 0;
		} else if (!isset($b['order'])){
			return 0;
		} else if ($a['order'] == $b['order']){
			return 0;
		}
		return ($a['order'] < $b['order']) ? -1 : 1;
	}

	
	public function run() {
		
		$bi                       = 0;
		$ni                       = 0;
		$patternSubtypeSet        = false;
		$patternType              = "";
		$patternTypeDash          = "";
		$suffixRendered           =	Config::getOption("outputFileSuffixes.rendered");
		
		$navItems                 = array();
		$navItems["patternTypes"] = array();
		
		// iterate over the different categories and add them to the navigation
		foreach ($this->store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "patternType") {

				$bi = (count($navItems["patternTypes"]) == 0) ? 0 : $bi + 1;
				
				// add a new patternType to the nav
				$navItems["patternTypes"][$bi] = array("patternTypeLC"    => strtolower($patternStoreData["nameClean"]),
													   "patternTypeUC"    => ucwords($patternStoreData["nameClean"]),
													   "patternType"      => $patternStoreData["name"],
													   "patternTypeDash"  => $patternStoreData["nameDash"],
													   "order"            => $patternStoreData["order"],
													   "patternTypeItems" => array(),
													   "patternItems"     => array());
				
				// starting a new set of pattern types. it might not have any pattern subtypes
				$patternSubtypeSet = false;
				$patternType       = $patternStoreData["name"];
				$patternTypeDash   = $patternStoreData["nameDash"];
				
			} else if ($patternStoreData["category"] == "patternSubtype") {
				
				$ni = (!$patternSubtypeSet) ? 0 : $ni + 1;
				
				// add a new patternSubtype to the nav
				$navItems["patternTypes"][$bi]["patternTypeItems"][$ni] = array("patternSubtypeLC"    => strtolower($patternStoreData["nameClean"]),
																				"patternSubtypeUC"    => ucwords($patternStoreData["nameClean"]),
																				"patternSubtype"      => $patternStoreData["name"],
																				"patternSubtypeDash"  => $patternStoreData["nameDash"],
																				"order"               => $patternStoreData["order"],
																				"patternSubtypeItems" => array());
				
				// starting a new set of pattern types. it might not have any pattern subtypes
				$patternSubtype     = $patternStoreData["name"];
				$patternSubtypeDash = $patternStoreData["nameDash"];
				$patternSubtypeSet  = true;
				
			} else if ($patternStoreData["category"] == "pattern") {
				
				if (isset($patternStoreData["hidden"]) && !$patternStoreData["hidden"]) {
					
					// set-up the info for the nav
					$patternInfo = array("patternPath"    => $patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].$suffixRendered.".html",
										 "patternSrcPath" => $patternStoreData["pathName"],
										 "patternName"    => ucwords($patternStoreData["nameClean"]),
										 "patternState"   => $patternStoreData["state"],
										 "order"          => $patternStoreData["order"],
										 "patternPartial" => $patternStoreData["partial"]);
					
					// add to the nav
					if ($patternStoreData["depth"] == 1) {
						$navItems["patternTypes"][$bi]["patternItems"][] = $patternInfo;
					} else {
						$navItems["patternTypes"][$bi]["patternTypeItems"][$ni]["patternSubtypeItems"][] = $patternInfo;
					}
				}
			}
		}
		
		// review each subtype. add a view all link or remove the subtype as necessary
		foreach ($navItems["patternTypes"] as $patternTypeKey => $patternTypeValues) {
			
			$reset           = false;
			$patternType     = $patternTypeValues["patternType"];
			$patternTypeDash = $patternTypeValues["patternTypeDash"];
			
			if (!in_array($patternType,$this->styleGuideExcludes)) {
				
				foreach ($patternTypeValues["patternTypeItems"] as $patternSubtypeKey => $patternSubtypeValues) {
					
					// if there are no sub-items in a section remove it
					if (empty($patternSubtypeValues["patternSubtypeItems"])) {
						
						unset($navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]);
						$reset = true;
						
					} else {
						
						$patternSubtype     = $patternSubtypeValues["patternSubtype"];
						$patternSubtypeDash = $patternSubtypeValues["patternSubtypeDash"];
						$subItemsCount      = count($patternSubtypeValues["patternSubtypeItems"]);
						
						// add a view all link
						$navItems["patternTypes"][$patternTypeKey]["patternTypeItems"][$patternSubtypeKey]["patternSubtypeItems"][$subItemsCount] = array(
																												 "patternPath"    => $patternType."-".$patternSubtype."/index.html",
																												 "patternName"    => "View All",
																												 "patternType"    => $patternType,
																												 "patternSubtype" => $patternSubtype,
																												 "patternPartial" => "viewall-".$patternTypeDash."-".$patternSubtypeDash);
						
					}
					
				}
				
			}
			
			if ($reset) {
				$navItems["patternTypes"][$patternTypeKey]["patternTypeItems"] = array_values($navItems["patternTypes"][$patternTypeKey]["patternTypeItems"]);
				$reset = false;
			}
			
			// add an overall view all link to the menus with sub-menus
			if (!empty($navItems["patternTypes"][$patternTypeKey]["patternTypeItems"])) {
				
				$navItems["patternTypes"][$patternTypeKey]["patternItems"][] = array("patternPath"    => $patternType."/index.html",
																					 "patternName"    => "View All",
																					 "patternType"    => $patternType,
																					 "patternSubtype" => "all",
																					 "patternPartial" => "viewall-".$patternTypeDash."-all");
				
			}
		}


		// Sort the navItems by order property before returning final navigation (@TODO: look into possibly moving the sortNavByOrder function to a more global (ie. reusable) place)
		foreach ($navItems as $navItem) {
			// Sort top level patternTypes (ex. 01-atoms)
			usort($navItems['patternTypes'], array( $this, 'sortNavByOrder' ) ); 
			
			foreach ($navItem as $patternTypeKey => $patternTypeValue) {
				// Then sort patternTypeItems and/or patternItems depending on what exists (ex. Homepage or Buttons)
				usort($navItems['patternTypes'][$patternTypeKey]['patternTypeItems'], array( $this, 'sortNavByOrder' ) ); 
				usort($navItems['patternTypes'][$patternTypeKey]['patternItems'], array( $this, 'sortNavByOrder' ) );

				// Finallly, finish sorting out the nested patternSubtypeItems (Primary Button, etc)
				for($i = 0, $c = count($navItems['patternTypes'][$patternTypeKey]['patternTypeItems']); $i < $c; $i++){
					usort($navItems['patternTypes'][$patternTypeKey]['patternTypeItems'][$i]['patternSubtypeItems'], array( $this, 'sortNavByOrder' ) );
				}
			}
		}

		return $navItems;
	}
}
