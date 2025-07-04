<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Loggers;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Logger;

class Null_Logger_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_a_logger(): void {
		$logger = new Null_Logger();
		$this->assertInstanceOf( Logger::class, $logger );
	}

	/**
	 * @test
	 */
	public function it_should_not_throw_errors_when_logging(): void {
		$logger = new Null_Logger();
		$logger->info( 'test' );
		$this->assertTrue( true ); // If we get here, no error was thrown.
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_for_logs(): void {
		$logger = new Null_Logger();
		$this->assertEquals( [], $logger->retrieve_logs( 123 ) );
	}
}
