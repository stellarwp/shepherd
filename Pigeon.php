<?php

namespace StellarWP;

use StellarWP\Pigeon\Provider;

require_once dirname( __FILE__ ) . '/vendor/autoload.php';
class Pigeon {

	public static $enabled = false;

	public static $provider;

	public static $toggle_option_name = 'enable_pigeon';

	public static function init() {
		if ( ! static::is_enabled() ) {
			return;
		}

		tribe_register_provider( Provider::class );
	}

	public static function is_enabled() {
		return
			\tribe_get_option( self::$toggle_option_name, self::$enabled ) ||
			( defined( 'STELLARWP_PIGEON_ENABLE' ) && STELLARWP_PIGEON_ENABLE );
	}
}

