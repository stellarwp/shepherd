<?php
/**
 * Pigeon Config
 *
 * @package StellarWP\Pigeon\Config
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use RuntimeException;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;

/**
 * Pigeon Config
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Config
 */
class Config {
	/**
	 * The hook prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static string $hook_prefix;

	/**
	 * The logger.
	 *
	 * @since TBD
	 *
	 * @var ?Logger
	 */
	protected static ?Logger $logger = null;

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
	 * Gets the logger.
	 *
	 * @since TBD
	 *
	 * @return Logger
	 */
	public static function get_logger(): Logger {
		if ( ! static::$logger ) {
			static::$logger = new DB_Logger();
		}

		return static::$logger;
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
	 * @since TBD
	 *
	 * @param ?Logger $logger The logger.
	 *
	 * @return void
	 */
	public static function set_logger( ?Logger $logger ): void {
		static::$logger = $logger;
	}

	/**
	 * Resets the config.
	 *
	 * @return void
	 */
	public static function reset(): void {
		static::$hook_prefix = '';
		static::$logger      = null;
	}
}
