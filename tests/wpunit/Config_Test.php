<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use RuntimeException;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Shepherd\Loggers\Null_Logger;

class Config_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_get_the_container(): void {
		$container = tests_shepherd_get_container();

		$this->assertInstanceOf( ContainerInterface::class, $container );
		$this->assertInstanceOf( ContainerInterface::class, Config::get_container() );
		$this->assertSame( $container, Config::get_container() );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_hook_prefix(): void {
		Config::set_hook_prefix( 'my_prefix' );
		$this->assertEquals( 'my_prefix', Config::get_hook_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_when_accesing_hook_prefix_when_not_set(): void {
		$this->expectException( RuntimeException::class );
		Config::get_hook_prefix();
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_if_setting_empty_hook_prefix(): void {
		$this->expectException( RuntimeException::class );
		Config::set_hook_prefix( '' );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_db_logger_if_none_is_set(): void {
		$this->assertInstanceOf( ActionScheduler_DB_Logger::class, Config::get_logger() );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_logger(): void {
		$null_logger = new Null_Logger();
		Config::set_logger( $null_logger );
		$this->assertSame( $null_logger, Config::get_logger() );
	}

	/**
	 * @test
	 */
	public function it_should_get_and_set_render_admin_ui(): void {
		$this->assertFalse( Config::get_render_admin_ui() );

		// Test setting to false.
		Config::set_render_admin_ui( true );
		$this->assertTrue( Config::get_render_admin_ui() );

		// Reset to default.
		Config::set_render_admin_ui( false );
		$this->assertFalse( Config::get_render_admin_ui() );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_admin_page_title(): void {
		Config::set_hook_prefix( 'test_foo' );
		$expected = sprintf( __( 'Shepherd (%s)', 'stellarwp-shepherd' ), 'test_foo' );
		$this->assertEquals( $expected, Config::get_admin_page_title() );
	}

	/**
	 * @test
	 */
	public function it_should_use_custom_admin_page_title_callback(): void {
		Config::set_admin_page_title_callback( fn() => 'Custom Admin Page Title' );

		$this->assertEquals( 'Custom Admin Page Title', Config::get_admin_page_title() );

		// Clean up.
		Config::set_admin_page_title_callback( null );
	}

	/**
	 * @test
	 */
	public function it_should_fallback_to_default_if_callback_returns_non_string(): void {
		Config::set_admin_page_title_callback( fn() => 123 );
		Config::set_hook_prefix( 'test_foo' );

		$expected = sprintf( __( 'Shepherd (%s)', 'stellarwp-shepherd' ), 'test_foo' );
		$this->assertEquals( $expected, Config::get_admin_page_title() );

		// Clean up.
		Config::set_admin_page_title_callback( null );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_admin_menu_title(): void {
		Config::set_hook_prefix( 'bar_foo' );
		$expected = sprintf( __( 'Shepherd (%s)', 'stellarwp-shepherd' ), 'bar_foo' );
		$this->assertEquals( $expected, Config::get_admin_menu_title() );
	}

	/**
	 * @test
	 */
	public function it_should_use_custom_admin_menu_title_callback(): void {
		Config::set_admin_menu_title_callback( fn() => 'Custom Menu Title' );

		$this->assertEquals( 'Custom Menu Title', Config::get_admin_menu_title() );

		// Clean up.
		Config::set_admin_menu_title_callback( null );
	}

	/**
	 * @test
	 */
	public function it_should_get_default_admin_page_in_page_title(): void {
		Config::set_hook_prefix( 'baz_foo' );
		$expected = sprintf( __( 'Shepherd Task Manager (via %s)', 'stellarwp-shepherd' ), 'baz_foo' );
		$this->assertEquals( $expected, Config::get_admin_page_in_page_title() );
	}

	/**
	 * @test
	 */
	public function it_should_use_custom_admin_page_in_page_title_callback(): void {
		Config::set_admin_page_in_page_title_callback( fn() => 'Custom In-Page Title' );

		$this->assertEquals( 'Custom In-Page Title', Config::get_admin_page_in_page_title() );

		// Clean up.
		Config::set_admin_page_in_page_title_callback( null );
	}

	/**
	 * @test
	 */
	public function it_should_get_and_set_admin_page_capability(): void {
		// Test default capability.
		$this->assertEquals( 'manage_options', Config::get_admin_page_capability() );

		// Test setting custom capability.
		Config::set_admin_page_capability( 'edit_posts' );
		$this->assertEquals( 'edit_posts', Config::get_admin_page_capability() );

		// Test setting another capability.
		Config::set_admin_page_capability( 'administrator' );
		$this->assertEquals( 'administrator', Config::get_admin_page_capability() );

		// Reset to default.
		Config::set_admin_page_capability( 'manage_options' );
	}

	/**
	 * @before
	 */
	public function it_should_set_and_get_logger_when_null(): void {
		Config::reset();
		Config::set_container( tests_shepherd_get_container() );
	}

	/**
	 * @after
	 */
	public function reset(): void {
		tests_shepherd_reset_config();
	}
}
