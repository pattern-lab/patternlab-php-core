<?php

/*!
 * Pattern Data Data Link Exporter Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Populate the data.link attribute
 *
 */

namespace PatternLab\PatternData\Exporters;

use \PatternLab\Data;
use \PatternLab\PatternData;
use \PatternLab\Timer;

class DataLinkExporter extends \PatternLab\PatternData\Exporter {
	
	protected $store;
	
	public function __construct($options = array()) {
		
		parent::__construct($options);
		
		$this->store = PatternData::get();
		
	}
	
	public function run() {
		
		foreach ($this->store as $patternStoreKey => $patternStoreData) {
			
			if ($patternStoreData["category"] == "pattern") {
				
				if (isset($patternStoreData["pathDash"])) {
					$value = "../../patterns/".$patternStoreData["pathDash"]."/".$patternStoreData["pathDash"].".html";
					Data::setOptionLink($patternStoreKey, $value);
				}
				
			}
			
		}
		
	}
	
}
