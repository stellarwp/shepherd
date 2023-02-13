<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Templates\Template_Interface;

interface Module_Interface {

	public static function init();
	public function send( Entry $entry );
}