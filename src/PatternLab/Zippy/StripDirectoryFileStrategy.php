<?php

namespace PatternLab\Zippy;

use \Alchemy\Zippy\Adapter\AdapterContainer;
use \Alchemy\Zippy\FileStrategy\FileStrategyInterface;
use \PatternLab\Zippy\StripDirectoryAdapter;

class StripDirectoryFileStrategy implements FileStrategyInterface {
	
	public function __construct() {
		$this->container = AdapterContainer::load();
	}
	
	public function getAdapters() {
		return array(StripDirectoryAdapter::newInstance($this->container['executable-finder'],$this->container['resource-manager'],$this->container['gnu-tar.inflator'],$this->container['gnu-tar.deflator']));
	}
	
	public function getFileExtension() {
		return 'tar.gz';
	}
	
}
