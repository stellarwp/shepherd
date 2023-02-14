<?php

namespace StellarWP\Pigeon;

use StellarWP\ContainerContract\ContainerInterface;

class Pigeon {

	public static $enabled = false;

	protected static $container;

	protected static $instance;

	public static function init( ContainerInterface $container ) {
		if ( ! static::is_enabled() ) {
			return;
		}

		static::$enabled = true;

		if ( static::$instance instanceof Pigeon ) {
			return static::$instance;
		}

		static::$instance = new Pigeon();
		static::$container = $container;
		static::$container->register( Provider::class );

		return static::$instance;
	}

	public static function is_enabled() {
		return
			( defined( 'STELLARWP_PIGEON_ENABLE' ) && STELLARWP_PIGEON_ENABLE );
	}

	public static function get_container() {
		return static::$container;
	}
}

