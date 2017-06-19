<?php

/*!
 * Pattern Data Rule Class
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 */

namespace PatternLab\PatternData;

use \PatternLab\Timer;

class Rule {
	
	protected $depthProp;
	protected $extProp;
	protected $isDirProp;
	protected $isFileProp;
	protected $searchProp;
	protected $ignoreProp;
	
	public function __construct($options) {
		
		// nothing here yet
		
	}
	
	/**
	* Test the Pattern Data Rules to see if a Rule matches the supplied values
	* @param  {Integer}      the depth of the item
	* @param  {String}       the extension of the item
	* @param  {Boolean}      if the item is a directory
	* @param  {Boolean}      if the item is a file
	* @param  {String}       the name of the item
	*
	* @return {Boolean}      whether the test was succesful or not
	*/
	public function test($depth, $ext, $isDir, $isFile, $name) {
		
		if (($this->depthProp != 3) && ($depth != $this->depthProp)) {
			return false;
		}
		
		if (($this->compareProp($ext,$this->extProp, true)) && ($isDir == $this->isDirProp) && ($isFile == $this->isFileProp)) {
			$result = true;
			if ($this->searchProp != "") {
				$result = $this->compareProp($name,$this->searchProp);
			}
			if ($this->ignoreProp != "") {
				$result = ($this->compareProp($name,$this->ignoreProp)) ? false : true;
			}
			return $result;
		}
		
		return false;
		
	}
	
	/**
	* Compare the search and ignore props against the name.
	* Can use && or || in the comparison
	* @param  {String}       the name of the item
	* @param  {String}       the value of the property to compare
	*
	* @return {Boolean}      whether the compare was successful or not
	*/
	protected function compareProp($name, $propCompare, $exact = false) {
		
		if (($name == "") && ($propCompare == "")) {
			$result = true;
		} else if ((($name == "") && ($propCompare != "")) || (($name != "") && ($propCompare == ""))) {
			$result = false;
		} else if (strpos($propCompare,"&&") !== false) {
			$result = true;
			$props  = explode("&&",$propCompare);
			foreach ($props as $prop) {
				$pos    = $this->testProp($name, $prop, $exact);
				$result = ($result && $pos);
			}
		} else if (strpos($propCompare,"||") !== false) {
			$result = false;
			$props  = explode("||",$propCompare);
			foreach ($props as $prop) {
				$pos    = $this->testProp($name, $prop, $exact);
				$result = ($result || $pos);
			}
		} else {
			$result = $this->testProp($name, $propCompare, $exact);
		}
		
		return $result;
		
	}
	
	/**
	* Get the name for a given pattern sans any possible digits used for reordering
	* @param  {String}       the pattern based on the filesystem name
	* @param  {Boolean}      whether or not to strip slashes from the pattern name
	*
	* @return {String}       a lower-cased version of the pattern name
	*/
	protected function getPatternName($pattern, $clean = true) {
		$patternBits = explode("-",$pattern,2);
		$patternName = (((int)$patternBits[0] != 0) || ($patternBits[0] == '00')) ? $patternBits[1] : $pattern;
    // replace possible dots with dashes. pattern names cannot contain dots
    // since they are used as id/class names in the styleguidekit.
    $patternName = str_replace('.', '-', $patternName);
		return ($clean) ? (str_replace("-"," ",$patternName)) : $patternName;
	}
	
	/**
	* Get the value for a property on the current PatternData rule
	* @param  {String}       the name of the property
	*
	* @return {Boolean}      whether the set was successful
	*/
	public function getProp($propName) {
		
		if (isset($this->$propName)) {
			return $this->$propName;
		}
		
		return false;
		
	}
	
	/**
	* Set a value for a property on the current PatternData rule
	* @param  {String}       the name of the property
	* @param  {String}       the value of the property
	*
	* @return {Boolean}      whether the set was successful
	*/
	public function setProp($propName, $propValue) {
		
		$this->$propName = $this->$propValue;
		return true;
		
	}
	
	/**
	* Test the given property
	* @param  {String}       the value of the property to be tested
	* @param  {String}       the value of the property to be tested against
	* @param  {Boolean}      if this should be an exact match or just a string appearing in another
	*
	* @return {Boolean}      the test result
	*/
	protected function testProp($propToTest, $propToTestAgainst, $beExact) {
		if ($beExact) {
			$result = ($propToTest === $propToTestAgainst);
		} else {
			$result = (strpos($propToTest,$propToTestAgainst) !== false) ? true : false;
		}
		return $result;
	}
	
	/**
	* Update a property on a given rule
	* @param  {String}       the name of the property
	* @param  {String}       the value of the property
	* @param  {String}       the action that should be taken with the new value
	*
	* @return {Boolean}      whether the update was successful
	*/
	public function updateProp($propName, $propValue, $action = "or") {
		
		if (!isset($this->$propName) || !is_scalar($propValue)) {
			return false;
		}
		
		if ($action == "or") {
			
			$propValue = $this->$propName."||".$propValue;
			
		} else if ($action == "and") {
			
			$propValue = $this->$propName."&&".$propValue;
			
		}
		
		return $this->setProp($this->$propName,$propValue);
		
	}
	
}
