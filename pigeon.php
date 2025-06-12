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

namespace StellarWP\Pigeon;

defined( 'ABSPATH' ) || exit;

if ( class_exists( Pigeon::class ) ) {
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
