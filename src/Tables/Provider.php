<?php
/**
 * Pigeon Tables Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */

declare(strict_types=1);

namespace StellarWP\Pigeon\Tables;

use StellarWP\Pigeon\Abstracts\Provider_Abstract;
use StellarWP\Schema\Register;

/**
 * Pigeon Tables Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */
class Provider extends Provider_Abstract {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void {
		Register::table( Tasks::class );
	}
}
