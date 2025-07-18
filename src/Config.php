<?php
/**
 * Shepherd Config
 *
 * @package StellarWP\Shepherd\Config
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;

/**
 * Shepherd Config
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Config
 */
class Config {
	/**
	 * Container object.
	 *
	 * @since 0.0.1
	 *
	 * @var ?ContainerInterface
	 */
	protected static ?ContainerInterface $container = null;

	/**
	 * The hook prefix.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static string $hook_prefix;

	/**
	 * The logger.
	 *
	 * @since 0.0.1
	 *
	 * @var ?Logger
	 */
	protected static ?Logger $logger = null;

	/**
	 * The maximum table name length.
	 *
	 * @since 0.0.1
	 *
	 * @var int
	 */
	protected static int $max_table_name_length = 64;

	/**
	 * Get the container.
	 *
	 * @since 0.0.1
	 *
	 * @throws RuntimeException If the container is not set.
	 *
	 * @return ContainerInterface
	 */
	public static function get_container(): ContainerInterface {
		if ( self::$container === null ) {
			throw new RuntimeException( 'You must provide a container via StellarWP\Shepherd\Config::set_container() before attempting to fetch it.' );
		}

		return self::$container;
	}

	/**
	 * Gets the hook prefix.
	 *
	 * @since 0.0.1
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
	 * Gets the logger.
	 *
	 * @since 0.0.1
	 *
	 * @return Logger
	 */
	public static function get_logger(): Logger {
		if ( ! static::$logger ) {
			static::$logger = new ActionScheduler_DB_Logger();
		}

		return static::$logger;
	}

	/**
	 * Gets the maximum table name length.
	 *
	 * @return int
	 */
	public static function get_max_table_name_length(): int {
		return static::$max_table_name_length;
	}

	/**
	 * Returns whether the container has been set.
	 *
	 * @return bool
	 */
	public static function has_container(): bool {
		return self::$container !== null;
	}

	/**
	 * Set the container object.
	 *
	 * @param ContainerInterface $container Container object.
	 */
	public static function set_container( ContainerInterface $container ) {
		self::$container = $container;
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

	/**
	 * Sets the logger.
	 *
	 * @since 0.0.1
	 *
	 * @param ?Logger $logger The logger.
	 *
	 * @return void
	 */
	public static function set_logger( ?Logger $logger ): void {
		static::$logger = $logger;
	}

	/**
	 * Sets the maximum table name length.
	 *
	 * @since 0.0.1
	 *
	 * @param int $length The maximum table name length.
	 *
	 * @return void
	 */
	public static function set_max_table_name_length( int $length ): void {
		static::$max_table_name_length = $length;
	}

	/**
	 * Resets the config.
	 *
	 * @return void
	 */
	public static function reset(): void {
		static::$container             = null;
		static::$hook_prefix           = '';
		static::$logger                = null;
		static::$max_table_name_length = 64;
	}
}
