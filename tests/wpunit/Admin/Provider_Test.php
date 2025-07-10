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
		Config::set_render_admin_ui( true );
		$this->get_admin_provider()->register();
	}

	/**
	 * @after
	 */
	public function reset_config(): void {
		Config::set_render_admin_ui( false );
	}

	protected function get_admin_provider(): Provider {
		return Config::get_container()->get( Provider::class );
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

	/**
	 * @test
	 */
	public function it_should_enqueue_admin_page_assets(): void {
		global $wp_scripts, $wp_styles;

		$provider = $this->get_admin_provider();
		$provider->enqueue_admin_page_assets();

		// Check script is enqueued.
		$this->assertArrayHasKey( 'shepherd-admin-script', $wp_scripts->registered );
		$this->assertContains( 'wp-components', $wp_scripts->registered['shepherd-admin-script']->deps );
		$this->assertContains( 'wp-data', $wp_scripts->registered['shepherd-admin-script']->deps );

		// Check style is enqueued.
		$this->assertArrayHasKey( 'shepherd-admin-style', $wp_styles->registered );
	}

	/**
	 * @test
	 */
	public function it_should_localize_script_data(): void {
		global $wp_scripts;

		$provider = $this->get_admin_provider();
		$provider->enqueue_admin_page_assets();

		// Check localized data exists.
		$localized = $wp_scripts->get_data( 'shepherd-admin-script', 'data' );
		$this->assertStringContainsString( 'shepherdData', $localized );
		$this->assertStringContainsString( 'tasks', $localized );
		$this->assertStringContainsString( 'totalItems', $localized );
		$this->assertStringContainsString( 'totalPages', $localized );
	}

	/**
	 * @test
	 */
	public function it_should_get_task_status_correctly(): void {
		$provider = $this->get_admin_provider();

		// Use reflection to access protected method.
		$method = new \ReflectionMethod( $provider, 'get_task_status' );
		$method->setAccessible( true );

		// Test cancelled status.
		$cancelled_action = $this->createMock( \ActionScheduler_CanceledAction::class );
		$status = $method->invoke( $provider, $cancelled_action, null );
		$this->assertEquals( 'cancelled', $status['slug'] );
		$this->assertEquals( __( 'Cancelled', 'stellarwp-pigeon' ), $status['label'] );

		// Test finished/success status.
		$finished_action = $this->createMock( \ActionScheduler_FinishedAction::class );
		$status = $method->invoke( $provider, $finished_action, null );
		$this->assertEquals( 'success', $status['slug'] );
		$this->assertEquals( __( 'Success', 'stellarwp-pigeon' ), $status['label'] );

		// Test pending status (no log).
		$regular_action = $this->createMock( \ActionScheduler_Action::class );
		$status = $method->invoke( $provider, $regular_action, null );
		$this->assertEquals( 'pending', $status['slug'] );
		$this->assertEquals( __( 'Pending', 'stellarwp-pigeon' ), $status['label'] );

		// Test running status with started log.
		$started_log = $this->createMock( \StellarWP\Pigeon\Log::class );
		$started_log->method( 'get_type' )->willReturn( \StellarWP\Pigeon\Log::TYPE_STARTED );
		$status = $method->invoke( $provider, $regular_action, $started_log );
		$this->assertEquals( 'running', $status['slug'] );
		$this->assertEquals( __( 'Running', 'stellarwp-pigeon' ), $status['label'] );

		// Test failed status.
		$failed_log = $this->createMock( \StellarWP\Pigeon\Log::class );
		$failed_log->method( 'get_type' )->willReturn( \StellarWP\Pigeon\Log::TYPE_FAILED );
		$status = $method->invoke( $provider, $regular_action, $failed_log );
		$this->assertEquals( 'failed', $status['slug'] );
		$this->assertEquals( __( 'Failed', 'stellarwp-pigeon' ), $status['label'] );
	}
}
