<?php
/**
 * Pigeon's main service provider.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use StellarWP\Pigeon\Abstracts\Provider_Abstract;
use StellarWP\ContainerContract\ContainerInterface as Container;
use StellarWP\Pigeon\Tables\Provider as Tables_Provider;
use RuntimeException;
use StellarWP\Schema\Config as Schema_Config;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Contracts\Logger;

/**
 * Main Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Provider extends Provider_Abstract {
	/**
	 * The version of the plugin.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const VERSION = '0.0.1';

	/**
	 * The hook prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static string $hook_prefix;

	/**
	 * The container.
	 *
	 * @since TBD
	 *
	 * @var ?Container
	 */
	private static ?Container $static_container = null;

	/**
	 * Whether the provider has been registered.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private static bool $has_registered = false;

	/**
	 * Registers Pigeon's specific providers and starts core functionality
	 *
	 * @since TBD
	 *
	 * @return void The method does not return any value.
	 */
	public function register(): void {
		if ( self::is_registered() ) {
			return;
		}

		$this->require_action_scheduler();

		self::$static_container = $this->container;

		Schema_Config::set_container( $this->container );
		Schema_Config::set_db( DB::class );
		$this->container->singleton( Logger::class, Config::get_logger() );
		$this->container->singleton( Tables_Provider::class );
		$this->container->singleton( Regulator::class );
		$this->container->get( Tables_Provider::class )->register();
		$this->container->get( Regulator::class )->register();

		self::$has_registered = true;
	}

	/**
	 * Requires Action Scheduler.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function require_action_scheduler(): void {
		require_once __DIR__ . '/../vendor/woocommerce/action-scheduler/action-scheduler.php';
	}

	/**
	 * Sets the container.
	 *
	 * @since TBD
	 *
	 * @param ?Container $container The container.
	 *
	 * @return void
	 */
	public static function set_container( ?Container $container ): void {
		self::$static_container = $container;
	}

	/**
	 * Resets the registered state.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$has_registered = false;
	}

	/**
	 * Gets the container.
	 *
	 * @since TBD
	 *
	 * @return Container
	 *
	 * @throws RuntimeException If the container has not been set.
	 */
	public static function get_container(): Container {
		if ( ! self::$static_container ) {
			throw new RuntimeException( 'The container has not been set.' );
		}

		return self::$static_container;
	}

	/**
	 * Checks if Pigeon is registered.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function is_registered(): bool {
		return self::$has_registered;
	}
}
