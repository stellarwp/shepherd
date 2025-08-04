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
use Closure;

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
	 * The admin page capability.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static string $admin_page_capability = 'manage_options';

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
	 * The render admin UI flag.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected static bool $render_admin_ui = false;

	/**
	 * The callback to get the admin page title.
	 *
	 * @since TBD
	 *
	 * @var ?Closure
	 */
	protected static ?Closure $get_admin_page_title_callback = null;

	/**
	 * The callback to get the admin menu title.
	 *
	 * @since TBD
	 *
	 * @var ?Closure
	 */
	protected static ?Closure $get_admin_menu_title_callback = null;

	/**
	 * The callback to get the admin page in page title.
	 *
	 * @since TBD
	 *
	 * @var ?Closure
	 */
	protected static ?Closure $get_admin_page_in_page_title_callback = null;

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
			throw new RuntimeException( __( 'You must provide a container via StellarWP\Shepherd\Config::set_container() before attempting to fetch it.', 'stellarwp-shepherd' ) );
		}

		return self::$container;
	}

	/**
	 * Gets the render admin UI flag.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function get_render_admin_ui(): bool {
		return static::$render_admin_ui;
	}

	/**
	 * Sets the render admin UI flag.
	 *
	 * @since TBD
	 *
	 * @param bool $render_admin_ui Whether to render the admin UI.
	 */
	public static function set_render_admin_ui( bool $render_admin_ui ): void {
		static::$render_admin_ui = $render_admin_ui;
	}

	/**
	 * Gets the admin page title.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_admin_page_title(): string {
		if ( is_callable( self::$get_admin_page_title_callback ) ) {
			$result = call_user_func( self::$get_admin_page_title_callback );
			if ( is_string( $result ) ) {
				return $result;
			}
		}

		/* translators: %s: The hook prefix for this instance */
		return sprintf( __( 'Shepherd (%s)', 'stellarwp-shepherd' ), static::$hook_prefix );
	}

	/**
	 * Sets the callback to get the admin page title.
	 *
	 * @since TBD
	 *
	 * @param ?Closure $callback The callback.
	 */
	public static function set_admin_page_title_callback( ?Closure $callback ): void {
		self::$get_admin_page_title_callback = $callback;
	}

	/**
	 * Gets the admin menu title.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_admin_menu_title(): string {
		if ( is_callable( self::$get_admin_menu_title_callback ) ) {
			$result = call_user_func( self::$get_admin_menu_title_callback );
			if ( is_string( $result ) ) {
				return $result;
			}
		}

		/* translators: %s: The hook prefix for this instance */
		return sprintf( __( 'Shepherd (%s)', 'stellarwp-shepherd' ), static::$hook_prefix );
	}

	/**
	 * Sets the callback to get the admin menu title.
	 *
	 * @since TBD
	 *
	 * @param ?Closure $callback The callback.
	 */
	public static function set_admin_menu_title_callback( ?Closure $callback ): void {
		self::$get_admin_menu_title_callback = $callback;
	}

	/**
	 * Gets the admin page in page title.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_admin_page_in_page_title(): string {
		if ( is_callable( self::$get_admin_page_in_page_title_callback ) ) {
			$result = call_user_func( self::$get_admin_page_in_page_title_callback );
			if ( is_string( $result ) ) {
				return $result;
			}
		}

		/* translators: %s: The hook prefix for this instance */
		return sprintf( __( 'Shepherd Task Manager (via %s)', 'stellarwp-shepherd' ), static::$hook_prefix );
	}

	/**
	 * Sets the callback to get the admin page in page title.
	 *
	 * @since TBD
	 *
	 * @param ?Closure $callback The callback.
	 */
	public static function set_admin_page_in_page_title_callback( ?Closure $callback ): void {
		self::$get_admin_page_in_page_title_callback = $callback;
	}

	/**
	 * Gets the admin page capability.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_admin_page_capability(): string {
		return self::$admin_page_capability;
	}

	/**
	 * Sets the admin page capability.
	 *
	 * @since TBD
	 *
	 * @param string $capability The capability.
	 */
	public static function set_admin_page_capability( string $capability ): void {
		self::$admin_page_capability = $capability;
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
	 * Gets the package URL.
	 *
	 * @since TBD
	 *
	 * @param string $path The path to the package.
	 *
	 * @return string
	 */
	public static function get_package_url( string $path = '' ): string {
		return plugin_dir_url( __DIR__ ) . ltrim( wp_normalize_path( $path ), '/' );
	}

	/**
	 * Gets the package path.
	 *
	 * @since TBD
	 *
	 * @param string $path The path to the package.
	 *
	 * @return string
	 */
	public static function get_package_path( string $path = '' ): string {
		return __DIR__ . '/../' . ltrim( wp_normalize_path( $path ), '/' );
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
		static::$container                             = null;
		static::$hook_prefix                           = '';
		static::$logger                                = null;
		static::$max_table_name_length                 = 64;
		static::$render_admin_ui                       = false;
		static::$get_admin_page_title_callback         = null;
		static::$get_admin_menu_title_callback         = null;
		static::$get_admin_page_in_page_title_callback = null;
		static::$admin_page_capability                 = 'manage_options';
	}
}
