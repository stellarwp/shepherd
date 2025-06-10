<?php

namespace StellarWP\Pigeon;

/**
 * Main Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Was this provider already registered
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private $has_registered = false;

	/**
	 * Registers Pigeon's specific providers and starts core functionality
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function register() :bool {
		if ( $this->has_registered ) {
			return false;
		}

		$this->container->register( Delivery\Provider::class );
		$this->container->register( Scheduling\Provider::class );
		$this->container->register( Templates\Provider::class );

		$this->register_hooks();
		$this->has_registered = true;

		return true;
	}

	/**
	 * Registers entry points for Pigeon's core functionality
	 *
	 * @since TBD
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_database' ], 2 );
	}

	/**
	 * Registers the necessary database structure
	 *
	 * @since TBD
	 */
	public function register_database(): void {
		$this->container->make( Database::class )->register();
	}
}