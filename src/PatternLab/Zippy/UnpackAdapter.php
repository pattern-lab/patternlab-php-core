<?php

/*!
 * Zippy Unpack Directory Adapter
 *
 * Copyright (c) 2014 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Gives zippy the ability to "unpack" a zip download from GitHub by modifying
 * the default TarGNUTarAdapter options.
 *
 */

namespace PatternLab\Zippy;

use \Alchemy\Zippy\Adapter\GNUTar\TarGNUTarAdapter;

class UnpackAdapter extends TarGNUTarAdapter {
	
	protected function getExtractOptions() {
		return array('--overwrite-dir', '--overwrite', '--strip-components=1');
	}
	
	protected function getExtractMembersOptions() {
		return array('--overwrite-dir', '--overwrite', '--strip-components=1');
	}
	
}