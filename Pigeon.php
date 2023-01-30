<?php

namespace StellarWP;

use StellarWP\Pigeon\Provider;

class Pigeon {

	public static $initialized = false;

	public static $module;

	public static function init() {
		if ( ! self::$initialized ) {
			return;
		}

		self::$module = new Provider();
		self::$module->register();
		self::$initialized = true;
	}

}
