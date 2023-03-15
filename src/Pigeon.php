<?php

namespace StellarWP\Pigeon;

use StellarWP\ContainerContract\ContainerInterface;

/**
 * Pigeon Message Delivery System
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */
class Pigeon {

	/**
	 * Is Pigeon enabled
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public static $enabled = false;

	/**
	 * The singleton instance of Pigeon
	 *
	 * @since TBD
	 *
	 * @var Pigeon
	 */
	protected static $instance;

	/**
	 * The DI container used to initialize Pigeon
	 *
	 * @since TBD
	 *
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Initialize Pigeon
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return Pigeon
	 */
	public static function get_instance(): Pigeon {
		return static::$instance;
	}

	/**
	 * Stores the Pigeon instance in a static property
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return ContainerInterface
	 */
	public function get_container(): ContainerInterface {
		return $this->container;
	}
}

define( 'STELLARWP_PIGEON_PATH', dirname( __DIR__ ) . '/' );
