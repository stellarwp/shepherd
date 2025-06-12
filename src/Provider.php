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

use lucatume\DI52\ServiceProvider;
use lucatume\DI52\Container;
use StellarWP\Pigeon\Tables\Provider as Tables_Provider;
use RuntimeException;

/**
 * Main Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Provider extends ServiceProvider {
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
	 * @var Container
	 */
	private static Container $static_container;

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
		if ( self::$has_registered ) {
			return;
		}

		self::$static_container = $this->container;

		$this->container->register( Tables_Provider::class );

		self::$has_registered = true;
	}

	/**
	 * Gets the container.
	 *
	 * @since TBD
	 *
	 * @return Container
	 */
	public static function get_container(): Container {
		if ( ! self::$static_container ) {
			self::$static_container = new Container();
		}

		return self::$static_container;
	}

	/**
	 * Gets the hook prefix.
	 *
	 * @since TBD
	 *
	 * @throws RuntimeException If the hook prefix is not set.
	 *
	 * @return string
	 */
	public static function get_hook_prefix(): string {
		if ( ! static::$hook_prefix ) {
			$class = __CLASS__;
			throw new RuntimeException( "You must specify a hook prefix for your project with {$class}::set_hook_prefix()" );
		}

		return static::$hook_prefix;
	}

	/**
	 * Sets the hook prefix.
	 *
	 * @param string $prefix The prefix to add to hooks.
	 *
	 * @throws RuntimeException If the hook prefix is empty.
	 *
	 * @return void
	 */
	public static function set_hook_prefix( string $prefix ): void {
		if ( ! $prefix ) {
			throw new RuntimeException( 'The hook prefix cannot be empty.' );
		}

		static::$hook_prefix = $prefix;
	}
}
