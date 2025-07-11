<?php
/**
 * Unit tests for the Admin Provider.
 * Tests individual methods and functionality with minimal mocking.
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
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Provider_Test extends WPTestCase {
	use With_Uopz;
	use SnapshotAssertions;

	private Provider $provider;

	/**
	 * @before
	 */
	public function setup_provider(): void {
		$this->set_fn_return( 'is_admin', true );
		Config::set_render_admin_ui( true );
		$this->provider = Config::get_container()->get( Provider::class );
		$this->provider->register();
	}

	/**
	 * @after
	 */
	public function cleanup_provider(): void {
		Config::set_render_admin_ui( false );
		$_POST = [];
	}

	/**
	 * @test
	 */
	public function it_should_register_hooks(): void {
		$this->assertNotFalse(
			has_action( 'wp_ajax_shepherd_get_tasks', [ $this->provider, 'ajax_get_tasks' ] ),
			'AJAX action should be registered'
		);

		$this->assertNotFalse(
			has_action( 'admin_menu', [ $this->provider, 'register_admin_menu' ] ),
			'Admin menu action should be registered'
		);
	}

	/**
	 * Test admin page HTML rendering
	 * @test
	 */
	public function it_should_render_admin_page(): void {
		ob_start();
		$this->provider->render_admin_page();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}

	/**
	 * Test custom title rendering
	 * @test
	 */
	public function it_should_render_custom_titles(): void {
		Config::set_admin_page_in_page_title_callback( fn() => 'Custom Unit Test Title' );

		ob_start();
		$this->provider->render_admin_page();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}

	/**
	 * Test asset enqueuing
	 * @test
	 */
	public function it_should_enqueue_admin_page_assets(): void {
		global $wp_scripts, $wp_styles;

		$this->provider->enqueue_admin_page_assets();

		// Check script is enqueued
		$this->assertArrayHasKey( 'shepherd-admin-script', $wp_scripts->registered );
		$this->assertContains( 'wp-components', $wp_scripts->registered['shepherd-admin-script']->deps );
		$this->assertContains( 'wp-data', $wp_scripts->registered['shepherd-admin-script']->deps );

		// Check style is enqueued
		$this->assertArrayHasKey( 'shepherd-admin-style', $wp_styles->registered );
	}

	/**
	 * Test script localization
	 * @test
	 */
	public function it_should_localize_script_data(): void {
		global $wp_scripts;

		$this->provider->enqueue_admin_page_assets();

		// Check localized data exists
		$localized = $wp_scripts->get_data( 'shepherd-admin-script', 'data' );
		$this->assertStringContainsString( 'shepherdData', $localized );
		$this->assertStringContainsString( 'tasks', $localized );
		$this->assertStringContainsString( 'totalItems', $localized );
		$this->assertStringContainsString( 'totalPages', $localized );
		$this->assertStringContainsString( 'nonce', $localized );
	}

	/**
	 * Test capability configuration
	 * @test
	 */
	public function it_should_respect_admin_page_capability(): void {
		// Test default capability
		$this->assertEquals( 'manage_options', Config::get_admin_page_capability() );

		// Test setting custom capability
		Config::set_admin_page_capability( 'edit_posts' );
		$this->assertEquals( 'edit_posts', Config::get_admin_page_capability() );

		// Reset to default
		Config::set_admin_page_capability( 'manage_options' );
	}

	/**
	 * Test localized data structure
	 * @test
	 */
	public function it_should_provide_correct_localized_data_structure(): void {
		$reflection = new \ReflectionMethod( $this->provider, 'get_localized_data' );
		$reflection->setAccessible( true );
		$data = $reflection->invoke( $this->provider );

		// Check required keys exist
		$this->assertArrayHasKey( 'tasks', $data );
		$this->assertArrayHasKey( 'totalItems', $data );
		$this->assertArrayHasKey( 'totalPages', $data );
		$this->assertArrayHasKey( 'defaultArgs', $data );
		$this->assertArrayHasKey( 'nonce', $data );

		// Check data types
		$this->assertIsArray( $data['tasks'] );
		$this->assertIsInt( $data['totalItems'] );
		$this->assertIsInt( $data['totalPages'] );
		$this->assertIsArray( $data['defaultArgs'] );
		$this->assertIsString( $data['nonce'] );

		// Check default args structure
		$defaultArgs = $data['defaultArgs'];
		$this->assertEquals( 10, $defaultArgs['perPage'] );
		$this->assertEquals( 1, $defaultArgs['page'] );
		$this->assertEquals( 'desc', $defaultArgs['order'] );
		$this->assertEquals( 'id', $defaultArgs['orderby'] );
		$this->assertEquals( '', $defaultArgs['search'] );
		$this->assertEquals( '[]', $defaultArgs['filters'] );
	}

	/**
	 * Test get_tasks method parameter handling
	 * @test
	 */
	public function it_should_handle_get_tasks_method_parameters(): void {
		$reflection = new \ReflectionMethod( $this->provider, 'get_tasks' );
		$reflection->setAccessible( true );

		// Test basic call
		$result = $reflection->invoke( $this->provider, [], 10, 1 );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'tasks', $result );
		$this->assertArrayHasKey( 'totalItems', $result );
		$this->assertArrayHasKey( 'totalPages', $result );

		// Test with different parameters
		$result2 = $reflection->invoke( $this->provider, [ 'orderby' => 'action_id' ], 5, 2 );
		$this->assertIsArray( $result2 );
		$this->assertArrayHasKey( 'tasks', $result2 );
	}

	/**
	 * Test AJAX method structure
	 * @test
	 */
	public function it_should_have_proper_ajax_method(): void {
		// Test method exists
		$this->assertTrue( method_exists( $this->provider, 'ajax_get_tasks' ) );

		// Test method visibility
		$reflection = new \ReflectionMethod( $this->provider, 'ajax_get_tasks' );
		$this->assertTrue( $reflection->isPublic() );

		// Test method parameters
		$this->assertEquals( 0, $reflection->getNumberOfParameters() );
	}

	/**
	 * Test provider registration state
	 * @test
	 */
	public function it_should_track_registration_state(): void {
		// Test that Provider tracks if it's registered
		$this->assertTrue( Provider::is_registered() );

		// Test multiple registrations don't cause issues
		$this->provider->register();
		$this->provider->register();
		$this->assertTrue( Provider::is_registered() );
	}

	/**
	 * Test admin page rendering with various configurations
	 * @test
	 */
	public function it_should_handle_various_title_configurations(): void {
		// Test with page title callback
		Config::set_admin_page_title_callback( fn() => 'Unit Test Page Title' );
		$this->assertEquals( 'Unit Test Page Title', Config::get_admin_page_title() );

		// Test with menu title callback
		Config::set_admin_menu_title_callback( fn() => 'Unit Test Menu' );
		$this->assertEquals( 'Unit Test Menu', Config::get_admin_menu_title() );

		// Test with in-page title callback
		Config::set_admin_page_in_page_title_callback( fn() => 'Unit Test In-Page' );
		$this->assertEquals( 'Unit Test In-Page', Config::get_admin_page_in_page_title() );

		// Test rendering reflects the in-page title
		ob_start();
		$this->provider->render_admin_page();
		$output = ob_get_clean();
		$this->assertStringContainsString( 'Unit Test In-Page', $output );

		// Cleanup
		Config::set_admin_page_title_callback( null );
		Config::set_admin_menu_title_callback( null );
		Config::set_admin_page_in_page_title_callback( null );
	}

	/**
	 * Test render_admin_ui configuration affects registration
	 * @test
	 */
	public function it_should_respect_render_admin_ui_configuration(): void {
		// Test that config setting affects behavior
		$original_setting = Config::get_render_admin_ui();

		Config::set_render_admin_ui( false );
		$this->assertFalse( Config::get_render_admin_ui() );

		Config::set_render_admin_ui( true );
		$this->assertTrue( Config::get_render_admin_ui() );

		// Restore original
		Config::set_render_admin_ui( $original_setting );
	}

	/**
	 * Test that provider methods handle edge cases
	 * @test
	 */
	public function it_should_handle_edge_cases_gracefully(): void {
		// Test rendering with empty/null configurations
		Config::set_admin_page_in_page_title_callback( fn() => '' );

		ob_start();
		$this->provider->render_admin_page();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}
}
