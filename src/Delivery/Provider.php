<?php

namespace StellarWP\Pigeon\Delivery;

use StellarWP\Pigeon\Delivery\Modules\Mail;

/**
 * Delivery Modules Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Registers Pigeon's delivery modules
	 *
	 * @since TBD
	 */
	public function register(): void {
		$this->register_module_mail();
	}

	/**
	 * Registers entry points for Pigeon's delivery modules
	 *
	 * @since TBD
	 */
	public function register_module_mail(): void {
		add_filter( 'pre_wp_mail', [ $this, 'intercept_outgoing_mail' ], 999, 5 );
	}

	/**
	 * Entry point for the Mail Delivery Module
	 *
	 * @since TBD
	 *
	 * @param ...$args these args are documented in core `pre_wp_mail` filter.
	 *
	 * @return null|bool. Returns null if the email was not intercepted. True if it was properly processed. False
	 *                    otherwise.
	 */
	public function intercept_outgoing_mail( ...$args ) {
		return $this->container->make( Mail::class )->intercept( $args );
	}

}