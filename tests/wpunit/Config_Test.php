<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;
use RuntimeException;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;

class Config_Test extends WPTestCase {
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
		$this->assertInstanceOf( DB_Logger::class, Config::get_logger() );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_logger(): void {
		/** @var Logger $mock_logger */
		$mock_logger = $this->createMock( Logger::class );
		Config::set_logger( $mock_logger );
		$this->assertSame( $mock_logger, Config::get_logger() );
	}

	/**
	 * @before
	 */
	public function it_should_set_and_get_logger_when_null(): void {
		Config::reset();
	}

	/**
	 * @after
	 */
	public function reset(): void {
		Config::set_logger( null );
		Config::set_hook_prefix( tests_pigeon_get_hook_prefix() );
	}
}
