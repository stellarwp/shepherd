<?php
/**
 * Shepherd Tables Service Provider
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Tables;
 */

declare(strict_types=1);

namespace StellarWP\Shepherd\Tables;

use StellarWP\Shepherd\Abstracts\Provider_Abstract;
use StellarWP\Schema\Register;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;

/**
 * Shepherd Tables Service Provider
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Tables;
 */
class Provider extends Provider_Abstract {
	/**
	 * Tables to register.
	 *
	 * @var array<int, class-string>
	 */
	private array $tables = [
		Tasks::class,
		Task_Logs::class,
	];

	/**
	 * Registers the service provider bindings.
	 *
	 * @since 0.0.1
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
