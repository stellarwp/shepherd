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
use StellarWP\Pigeon\Loggers\ActionScheduler_DB_Logger;

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
	 * The maximum safe hook prefix length.
	 *
	 * @since TBD
	 *
	 * @var ?int
	 */
	protected static ?int $max_hook_prefix_length = null;

	/**
	 * The maximum table name length.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const MAX_TABLE_NAME_LENGTH = 64;

	/**
	 * The longest table name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const LONGEST_TABLE_NAME = 'pigeon_%s_task_logs';

	/**
	 * Gets the maximum safe hook prefix length.
	 *
	 * Calculates the maximum length a hook prefix can be while ensuring
	 * table names don't exceed MySQL's 64-character limit.
	 *
	 * @since TBD
	 *
	 * @return int The maximum safe hook prefix length.
	 */
	public static function get_max_hook_prefix_length(): int {
		if ( null !== static::$max_hook_prefix_length ) {
			return static::$max_hook_prefix_length;
		}

		global $wpdb;

		$wp_prefix_length = strlen( $wpdb->prefix );

		$hook_prefix = static::get_hook_prefix();

		$base_name_length = strlen( sprintf( self::LONGEST_TABLE_NAME, $hook_prefix ) );

		return strlen( $hook_prefix ) + ( self::MAX_TABLE_NAME_LENGTH - $base_name_length - $wp_prefix_length );
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
	 * Gets the safe hook prefix.
	 *
	 * Returns the hook prefix trimmed to the maximum safe length
	 * to ensure table names don't exceed MySQL's limit.
	 *
	 * @since TBD
	 *
	 * @throws RuntimeException If the hook prefix is not set or the max hook prefix length could not be determined.
	 *
	 * @return string The safe hook prefix.
	 */
	public static function get_safe_hook_prefix(): string {
		$prefix     = static::get_hook_prefix();
		$max_length = static::get_max_hook_prefix_length();

		if ( ! $max_length ) {
			throw new RuntimeException( 'The max hook prefix could not be determined.' );
		}

		if ( strlen( $prefix ) > $max_length ) {
			return substr( $prefix, 0, $max_length );
		}

		return $prefix;
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
			static::$logger = new ActionScheduler_DB_Logger();
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
		static::$hook_prefix            = '';
		static::$logger                 = null;
		static::$max_hook_prefix_length = null;
	}
}
