<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Models\Entry;

interface Module_Interface {

	// public static function init();
	public static function send( Entry $entry );
}
