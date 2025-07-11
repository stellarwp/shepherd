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
