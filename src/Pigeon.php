<?php

namespace StellarWP\Pigeon;

use StellarWP\ContainerContract\ContainerInterface;

define( 'STELLARWP_PIGEON_PATH', dirname(__DIR__) . '/' );

class Pigeon {

	public static $enabled = false;

	protected static $instance;

	protected $container;

	public function init( ContainerInterface $container ) {
		if ( ! static::is_enabled() ) {
			return;
		}

		static::$enabled = true;

		if ( static::$instance instanceof Pigeon ) {
			return static::$instance;
		}

		static::$instance = new Pigeon();
		$this->container = $container;
		$this->container->register( Provider::class );

		return static::$instance;
	}

	public static function is_enabled() {
		return
			( defined( 'STELLARWP_PIGEON_ENABLE' ) && STELLARWP_PIGEON_ENABLE );
	}

	public function get_instance() {
		return static::$instance;
	}

	public function get_container() {
		return $this->container;
	}
}

