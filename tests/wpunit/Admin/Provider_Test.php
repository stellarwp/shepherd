<?php
/**
 * Tests for the Admin Provider.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Admin
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Admin;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Provider as Main_Provider;
use StellarWP\Pigeon\Tests\Traits\With_Uopz;

class Provider_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * Fakes the admin context.
	 *
	 * @before
	 */
	public function fake_admin_context(): void {
		$this->set_fn_return( 'is_admin', true );
		$this->get_admin_provider()->register();
	}

	protected function get_admin_provider(): Provider {
		return Main_Provider::get_container()->get( Provider::class );
	}

	/**
	 * @test
	 */
	public function it_should_register_admin_menu_action(): void {
		// Check that the action was added.
		$this->assertNotFalse( has_action( 'admin_menu', [ $this->get_admin_provider(), 'register_admin_menu' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_register_when_admin_ui_disabled(): void {
		remove_all_actions( 'admin_menu' );

		$this->set_fn_return( 'is_admin', false );
		$provider = $this->get_admin_provider();
		$provider->register();

		// Check that the action was NOT added.
		$this->assertFalse( has_action( 'admin_menu', [ $provider, 'register_admin_menu' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_render_admin_page(): void {
		// Create the provider.
		$provider = $this->get_admin_provider();

		// Capture the output.
		ob_start();
		$provider->render_admin_page();
		$output = ob_get_clean();

		// Check the output.
		$this->assertStringContainsString( '<div class="wrap">', $output );
		$this->assertStringContainsString( 'Pigeon Task Manager (via foobar)', $output );
		$this->assertStringContainsString( '<div id="shepherd-app"></div>', $output );
	}

	/**
	 * @test
	 */
	public function it_should_register_admin_page_with_custom_titles(): void {
		// Set custom title callbacks.
		Config::set_admin_page_title_callback( fn() => 'Custom Page Title' );
		Config::set_admin_menu_title_callback( fn() => 'Custom Menu Title' );
		Config::set_admin_page_in_page_title_callback( fn() => 'Custom In-Page Title' );

		$provider = $this->get_admin_provider();

		// Test the custom titles are returned.
		$this->assertEquals( 'Custom Page Title', Config::get_admin_page_title() );
		$this->assertEquals( 'Custom Menu Title', Config::get_admin_menu_title() );
		$this->assertEquals( 'Custom In-Page Title', Config::get_admin_page_in_page_title() );

		// Test rendering with custom title.
		ob_start();
		$provider->render_admin_page();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Custom In-Page Title', $output );

		// Clean up.
		Config::set_admin_page_title_callback( null );
		Config::set_admin_menu_title_callback( null );
		Config::set_admin_page_in_page_title_callback( null );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_admin_page_capability(): void {
		// Test default capability.
		$this->assertEquals( 'manage_options', Config::get_admin_page_capability() );

		// Test setting custom capability.
		Config::set_admin_page_capability( 'edit_posts' );
		$this->assertEquals( 'edit_posts', Config::get_admin_page_capability() );

		// Reset to default.
		Config::set_admin_page_capability( 'manage_options' );
	}
}