<?php

namespace PatternLab\Zippy;

use \Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter;

class StripDirectoryAdapter extends TarGNUTarAdapter {
	
	protected function getExtractOptions() {
		return array('--overwrite-dir', '--overwrite', '--strip-components=1');
	}
	
	protected function getExtractMembersOptions() {
		return array('--overwrite-dir', '--overwrite', '--strip-components=1');
	}
	
}
