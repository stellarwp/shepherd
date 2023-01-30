<?php

namespace StellarWP;

use StellarWP\Pigeon\Provider;

class Pigeon {

	public static $enabled = false;

	public static $provider;

	public static $toggle_option_name = 'enable_pigeon';

	public static function init() {
		if ( ! static::is_enabled() ) {
			return;
		}

		self::$provider = new Provider();
		self::$provider->register();
	}

	public static function is_enabled() {
		return
			\tribe_get_option( self::$toggle_option_name, self::$enabled ) ||
			( defined( 'STELLARWP_PIGEON_ENABLE' ) && STELLARWP_PIGEON_ENABLE );
	}

}

Pigeon::init();
