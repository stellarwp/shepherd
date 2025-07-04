<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Contracts\Logger;
use Psr\Log\LogLevel;

class Dummy_Loggable {
	use Loggable;
}

class Loggable_Test extends WPTestCase {
	private $mock_logger;

	/**
	 * @before
	 */
	public function set_up_mock_logger() {
		$this->mock_logger = $this->createMock( Logger::class );
		$container = Config::get_container();
		$container->singleton( Logger::class, $this->mock_logger );
	}

	/**
	 * @test
	 */
	public function it_should_log_created_message() {
		$this->mock_logger->expects( $this->once() )
			->method( 'log' )
			->with( LogLevel::INFO, 'Task 123 created.', $this->callback( function( $data ) {
				return $data['task_id'] === 123 && $data['type'] === 'created';
			} ) );

		$dummy = new Dummy_Loggable();
		$dummy->log_created( 123 );
	}

	/**
	 * @test
	 */
	public function it_should_log_starting_message() {
		$this->mock_logger->expects( $this->once() )
			->method( 'log' )
			->with( LogLevel::INFO, 'Task 123 starting.', $this->callback( function( $data ) {
				return $data['task_id'] === 123 && $data['type'] === 'started';
			} ) );

		$dummy = new Dummy_Loggable();
		$dummy->log_starting( 123 );
	}

	/**
	 * @test
	 */
	public function it_should_log_finished_message() {
		$this->mock_logger->expects( $this->once() )
			->method( 'log' )
			->with( LogLevel::INFO, 'Task 123 finished.', $this->callback( function( $data ) {
				return $data['task_id'] === 123 && $data['type'] === 'finished';
			} ) );

		$dummy = new Dummy_Loggable();
		$dummy->log_finished( 123 );
	}

	/**
	 * @test
	 */
	public function it_should_log_failed_message() {
		$this->mock_logger->expects( $this->once() )
			->method( 'log' )
			->with( LogLevel::ERROR, 'Task 123 failed.', $this->callback( function( $data ) {
				return $data['task_id'] === 123 && $data['type'] === 'failed';
			} ) );

		$dummy = new Dummy_Loggable();
		$dummy->log_failed( 123 );
	}
}
