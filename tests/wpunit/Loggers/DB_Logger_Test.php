<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Loggers;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Log;
use StellarWP\Pigeon\Tables\Task_Logs;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Tests\Container;
use StellarWP\Pigeon\Provider;

class DB_Logger_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_a_logger(): void {
		$logger = new DB_Logger();
		$this->assertInstanceOf( Logger::class, $logger );
		$this->assertInstanceOf( Logger::class, Config::get_logger() );
	}

	/**
	 * @test
	 */
	public function it_should_log_and_retrieve_messages(): void {
		$logger = new DB_Logger();
		$task_id = 123;
		$context = [
			'task_id' => $task_id,
			'type'    => 'created',
			'test'    => 1,
		];

		$logger->info( 'Test log entry', $context );

		$logs = $logger->retrieve_logs( $task_id );

		$this->assertCount( 1, $logs );
		$this->assertInstanceOf( Log::class, $logs[0] );
		$this->assertEquals( wp_json_encode( [ 'message' => 'Test log entry', 'context' => ['test' => 1 ] ] ), $logs[0]->get_entry() );
		$this->assertEquals( 'info', $logs[0]->get_level() );
		$this->assertEquals( 'created', $logs[0]->get_type() );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_when_no_logs_for_task(): void {
		$logger = new DB_Logger();
		$this->assertEquals( [], $logger->retrieve_logs( 999 ) );
	}
}
