<?php
/**
 * Integration tests for Admin Provider.
 * Tests real environment functionality with minimal mocking.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Admin
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Admin\Provider;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class Admin_Provider_Test extends WPTestCase {
	use With_Uopz;
	use SnapshotAssertions;

	private Provider $provider;

	/**
	 * @before
	 */
	public function setup_environment(): void {
		// Setup real admin environment
		$this->set_fn_return( 'is_admin', true );
		Config::set_render_admin_ui( true );

		$this->provider = Config::get_container()->get( Provider::class );
		$this->provider->register();
	}

	/**
	 * @after
	 */
	public function cleanup_environment(): void {
		$_POST = [];
		tests_shepherd_reset_config();
	}

	/**
	 * Test that the provider actually integrates with WordPress
	 * @test
	 */
	public function it_should_register_wordpress_hooks(): void {
		$this->assertTrue(
			has_action( 'admin_menu', [ $this->provider, 'register_admin_menu' ] ) !== false,
			'Provider should register admin menu hook'
		);

		$this->assertTrue(
			has_action( 'wp_ajax_shepherd_get_tasks', [ $this->provider, 'ajax_get_tasks' ] ) !== false,
			'Provider should register AJAX hook'
		);
	}

	/**
	 * Test real HTML output generation
	 * @test
	 */
	public function it_should_generate_admin_page_html(): void {
		// Capture actual HTML output
		ob_start();
		$this->provider->render_admin_page();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}

	/**
	 * Test real asset registration with WordPress
	 * @test
	 */
	public function it_should_enqueue_required_assets(): void {
		global $wp_scripts, $wp_styles;

		// Execute real asset enqueuing
		$this->provider->enqueue_admin_page_assets();

		// Verify assets are actually registered in WordPress globals
		$this->assertArrayHasKey( 'shepherd-admin-script', $wp_scripts->registered );
		$this->assertArrayHasKey( 'shepherd-admin-style', $wp_styles->registered );

		// Verify script has real dependencies
		$script = $wp_scripts->registered['shepherd-admin-script'];
		$this->assertIsArray( $script->deps );
		$this->assertContains( 'wp-components', $script->deps );
		$this->assertContains( 'wp-data', $script->deps );

		// Verify script has localized data
		$localized = $wp_scripts->get_data( 'shepherd-admin-script', 'data' );
		$this->assertNotEmpty( $localized );
		$this->assertStringContainsString( 'shepherdData', $localized );
	}

	/**
	 * Test real data structure without mocking
	 * @test
	 */
	public function it_should_provide_localized_data_structure(): void {
		$reflection = new \ReflectionMethod( $this->provider, 'get_localized_data' );
		$reflection->setAccessible( true );
		$data = $reflection->invoke( $this->provider );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'tasks', $data );
		$this->assertArrayHasKey( 'totalItems', $data );
		$this->assertArrayHasKey( 'totalPages', $data );
		$this->assertArrayHasKey( 'defaultArgs', $data );
		$this->assertArrayHasKey( 'nonce', $data );

		// Verify real nonce is generated
		$this->assertIsString( $data['nonce'] );
		$this->assertNotEmpty( $data['nonce'] );

		// Verify default arguments structure
		$defaultArgs = $data['defaultArgs'];
		$this->assertEquals( 10, $defaultArgs['perPage'] );
		$this->assertEquals( 1, $defaultArgs['page'] );
		$this->assertEquals( 'desc', $defaultArgs['order'] );
		$this->assertEquals( 'id', $defaultArgs['orderby'] );
		$this->assertEquals( '', $defaultArgs['search'] );
		$this->assertEquals( '[]', $defaultArgs['filters'] );
	}

	/**
	 * Test real configuration changes affect behavior
	 * @test
	 */
	public function it_should_respond_to_configuration_changes(): void {
		Config::set_admin_page_capability( 'edit_posts' );
		$this->assertEquals( 'edit_posts', Config::get_admin_page_capability() );

		// Test custom title callback
		Config::set_admin_page_in_page_title_callback( fn() => 'Integration Test Title' );

		// Test that real HTML output reflects configuration changes
		ob_start();
		$this->provider->render_admin_page();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}

	/**
	 * Test that disabling admin UI actually prevents registration
	 * @test
	 */
	public function it_should_handle_disabled_admin_ui(): void {
		// Remove existing hooks
		remove_all_actions( 'admin_menu' );
		remove_all_actions( 'wp_ajax_shepherd_get_tasks' );

		// Disable admin UI
		Config::set_render_admin_ui( false );

		$this->provider->register();

		// Verify hooks are NOT registered
		$this->assertFalse(
			has_action( 'admin_menu', [ $this->provider, 'register_admin_menu' ] ),
			'Admin menu should not be registered when UI is disabled'
		);

		$this->assertFalse(
			has_action( 'wp_ajax_shepherd_get_tasks', [ $this->provider, 'ajax_get_tasks' ] ),
			'AJAX handler should not be registered when UI is disabled'
		);
	}
}
