<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;
use RuntimeException;
use StellarWP\Pigeon\Loggers\Null_Logger;
use StellarWP\Pigeon\Loggers\ActionScheduler_DB_Logger;

class Config_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_get_the_container(): void {
		$container = tests_pigeon_get_container();

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
	public function it_should_calculate_max_hook_prefix_length(): void {
		global $wpdb;

		// Set a known hook prefix
		Config::set_hook_prefix( 'test' );

		// The new calculation is based on:
		// strlen($hook_prefix) + (64 - strlen(sprintf('pigeon_%s_task_logs', $hook_prefix)) - strlen($wpdb->prefix))
		$hook_prefix = 'test';
		$base_name_length = strlen( sprintf( 'pigeon_%s_task_logs', $hook_prefix ) );
		$expected = strlen( $hook_prefix ) + ( 64 - $base_name_length - strlen( $wpdb->prefix ) );

		$this->assertEquals( $expected, Config::get_max_hook_prefix_length() );
	}

	/**
	 * @test
	 */
	public function it_should_return_safe_hook_prefix_for_short_prefix(): void {
		Config::set_hook_prefix( 'short' );

		// Short prefix should be returned as-is
		$this->assertEquals( 'short', Config::get_safe_hook_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_trim_long_hook_prefix_to_safe_length(): void {
		$very_long_prefix = str_repeat( 'a', 100 ); // 100 characters
		Config::set_hook_prefix( $very_long_prefix );

		$safe_prefix = Config::get_safe_hook_prefix();
		$max_length = Config::get_max_hook_prefix_length();

		// Safe prefix should be trimmed to max length
		$this->assertEquals( $max_length, strlen( $safe_prefix ) );
		$this->assertEquals( substr( $very_long_prefix, 0, $max_length ), $safe_prefix );
	}

	/**
	 * @before
	 */
	public function it_should_set_and_get_logger_when_null(): void {
		Config::reset();
		Config::set_container( tests_pigeon_get_container() );
	}

	/**
	 * @after
	 */
	public function reset(): void {
		tests_pigeon_reset_config();
	}
}
