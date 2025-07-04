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
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;

/**
 * Pigeon Tables Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */
class Provider extends Provider_Abstract {
	/**
	 * Tables to register.
	 *
	 * @var array<string, class-string>
	 */
	private array $tables = [
		Tasks::class,
		Task_Logs::class,
	];

	/**
	 * Registers the service provider bindings.
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void {
		// Bind after all tables are registered.
		$this->container->singleton( Utility\Safe_Dynamic_Prefix::class );
		$this->container->get( Utility\Safe_Dynamic_Prefix::class )->calculate_longest_table_name( $this->tables );

		Register::table( Tasks::class );

		if ( $this->container->get( Logger::class ) instanceof DB_Logger ) {
			Register::table( Task_Logs::class );
		}
	}
}
