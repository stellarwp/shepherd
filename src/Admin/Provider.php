<?php
/**
 * Admin provider for Pigeon.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Admin
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Admin;

use StellarWP\Pigeon\Abstracts\Provider_Abstract;
use StellarWP\Pigeon\Config;

/**
 * Admin provider.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Admin
 */
class Provider extends Provider_Abstract {
	/**
	 * Whether the provider has been registered.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private static bool $has_registered = false;

	/**
	 * Registers the admin functionality.
	 *
	 * @since TBD
	 */
	public function register(): void {
		if ( self::is_registered() ) {
			return;
		}

		if ( ! Config::get_render_admin_ui() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

		self::$has_registered = true;
	}

	/**
	 * Registers the admin menu.
	 *
	 * @since TBD
	 */
	public function register_admin_menu(): void {
		$page_hook = add_management_page(
			Config::get_admin_page_title(),
			Config::get_admin_menu_title(),
			Config::get_admin_page_capability(),
			'pigeon-' . Config::get_hook_prefix(),
			[ $this, 'render_admin_page' ]
		);

		add_action( "admin_print_styles-{$page_hook}", [ $this, 'enqueue_admin_page_assets' ] );
	}

	/**
	 * Renders the admin page.
	 *
	 * @since TBD
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<h1>
				<?php echo esc_html( Config::get_admin_page_in_page_title() ); ?>
			</h1>
			<div id="shepherd-app"></div>
		</div>
		<?php
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

	/**
	 * Enqueues the admin page assets.
	 *
	 * @since TBD
	 */
	public function enqueue_admin_page_assets(): void {
		$asset_data = require Config::get_package_path( 'build/main.asset.php' );
		wp_enqueue_script( 'shepherd-admin-script', Config::get_package_url( 'build/main.js' ), $asset_data['dependencies'], $asset_data['version'], true );

		wp_enqueue_style( 'shepherd-admin-style', Config::get_package_url( 'build/style-main.css' ), [], $asset_data['version'] );
	}
}
