<?php
/**
 * Pigeon
 *
 * A library for offloading tasks to background processes.
 *
 * @package Pigeon
 *
 * @wordpress-plugin
 * Plugin Name: Pigeon
 * Description: A library for offloading tasks to background processes.
 * Version:     0.0.1
 * Author:      StellarWP
 * Author URI:  https://stellarwp.com
 * License:     GPL-2.0-or-later
 * Text Domain: stellarwp-pigeon
 * Domain Path: /languages
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

defined( 'ABSPATH' ) || exit;

/**
 * The point of this callback is to load the plugin from here as late as possible,
 * to allow third party systems using the library as a dependency to choose how and when they load it.
 *
 * So we are hooking very late into the init action, to give time to 3rd party systems to load the library as a dependency.
 *
 * @since TBD
 *
 * @return void The method does not return any value.
 */
add_action(
	'init',
	function (): void {
		if ( class_exists( Provider::class ) ) {
			// The library is already loaded as part of another's plugin dependency.
			return;
		}

		if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'You must run `composer install` to install the dependencies.', 'stellarwp-pigeon' ); ?></p>
					</div>
					<?php
				}
			);

			return;
		}

		require_once __DIR__ . '/vendor/autoload.php';

		$container = Provider::get_container();
		$container->register( Provider::class );
	},
	999999
);
