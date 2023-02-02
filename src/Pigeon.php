<?php

namespace StellarWP\Pigeon;

class Pigeon extends \tad_DI52_Container {

	public static $enabled = false;

	public static $instance;

	public static $toggle_option_name = 'enable_pigeon';

	public static function init() {
		if ( ! static::is_enabled() ) {
			return;
		}

		static::$enabled = true;

		if ( static::$instance instanceof Pigeon ) {
			return static::$instance;
		}

		static::$instance = new Pigeon();
		static::$instance->register( Provider::class );

		return static::$instance;
	}

	public static function is_enabled() {
		return
			( defined( 'STELLARWP_PIGEON_ENABLE' ) && STELLARWP_PIGEON_ENABLE );
	}
}

