<?php

namespace StellarWP\Pigeon;

use StellarWP\ContainerContract\ContainerInterface;

/**
 * Pigeon Message Delivery System
 *
 * @package StellarWP\Pigeon
 */
class Pigeon {

	/**
	 * Is Pigeon enabled
	 *
	 * @var bool
	 */
	public static $enabled = false;

	/**
	 * The singleton instance of Pigeon
	 *
	 * @var Pigeon
	 */
	protected static $instance;

	/**
	 * The DI container used to initialize Pigeon
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Initialize Pigeon
	 *
	 * @param ContainerInterface $container
	 *
	 * @return void
	 */
	public function init( ContainerInterface $container ) {
		if ( ! static::is_enabled() ) {
			return;
		}

		static::$enabled = true;

		if ( static::$instance instanceof Pigeon ) {
			return static::$instance;
		}

		static::set_instance( new Pigeon() );

		static::$instance->container = $container;
		static::$instance->container->register( Provider::class );
	}

	/**
	 * Static method to check if Pigeon has been enabled in the code
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return
			( defined( 'STELLARWP_PIGEON_ENABLE' ) && STELLARWP_PIGEON_ENABLE );
	}

	/**
	 * Returns the statically stored Pigeon instance
	 *
	 * @return Pigeon
	 */
	public static function get_instance(): Pigeon {
		return static::$instance;
	}

	/**
	 * Stores the Pigeon instance in a static property
	 *
	 * @param Pigeon $instance
	 *
	 * @return void
	 */
	public static function set_instance( Pigeon $instance ): void {
		static::$instance = $instance;
	}

	/**
	 * Returns the container used to initialize Pigeon
	 *
	 * @return ContainerInterface
	 */
	public function get_container(): ContainerInterface {
		return $this->container;
	}
}

define( 'STELLARWP_PIGEON_PATH', dirname( __DIR__ ) . '/' );
