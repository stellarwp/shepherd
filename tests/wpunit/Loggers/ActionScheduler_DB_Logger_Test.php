<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Loggers;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Log;
use StellarWP\Shepherd\Config;
use Psr\Log\InvalidArgumentException;
use StellarWP\DB\DB;

class ActionScheduler_DB_Logger_Test extends WPTestCase {
	/**
	 * @before
	 */
	public function set_action_scheduler_db_logger_as_default(): void {
		tests_shepherd_get_container()->singleton( Logger::class, ActionScheduler_DB_Logger::class );
	}

	/**
	 * @test
	 */
	public function it_should_be_a_logger(): void {
		$logger = new ActionScheduler_DB_Logger();
		$this->assertInstanceOf( Logger::class, $logger );
		$this->assertInstanceOf( ActionScheduler_DB_Logger::class, Config::get_logger() );
	}

	/**
	 * @test
	 */
	public function it_should_log_and_retrieve_messages(): void {
		$logger = new ActionScheduler_DB_Logger();
		$task_id = 123;
		$action_id = 456;
		$context = [
			'task_id'   => $task_id,
			'action_id' => $action_id,
			'type'      => 'created',
			'test'      => 1,
		];

		$logger->info( 'Test log entry', $context );

		$logs = $logger->retrieve_logs( $task_id );

		$this->assertCount( 1, $logs );
		$this->assertInstanceOf( Log::class, $logs[0] );
		$this->assertEquals( wp_json_encode( [ 'message' => 'Test log entry', 'context' => ['test' => 1 ] ] ), $logs[0]->get_entry() );
		$this->assertEquals( 'info', $logs[0]->get_level() );
		$this->assertEquals( 'created', $logs[0]->get_type() );
		$this->assertEquals( $task_id, $logs[0]->get_task_id() );
		$this->assertEquals( $action_id, $logs[0]->get_action_id() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_log_level(): void {
		$logger = new ActionScheduler_DB_Logger();
		
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid log level.' );
		
		$logger->log( 'invalid_level', 'Test message', [ 'task_id' => 1, 'action_id' => 1, 'type' => 'test' ] );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_when_task_id_is_missing(): void {
		$logger = new ActionScheduler_DB_Logger();
		
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Task ID is required.' );
		
		$logger->info( 'Test message', [ 'action_id' => 1, 'type' => 'test' ] );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_when_action_id_is_missing(): void {
		$logger = new ActionScheduler_DB_Logger();
		
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Action ID is required.' );
		
		$logger->info( 'Test message', [ 'task_id' => 1, 'type' => 'test' ] );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_when_type_is_missing(): void {
		$logger = new ActionScheduler_DB_Logger();
		
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Type is required.' );
		
		$logger->info( 'Test message', [ 'task_id' => 1, 'action_id' => 1 ] );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_when_no_logs_for_task(): void {
		$logger = new ActionScheduler_DB_Logger();
		$this->assertEquals( [], $logger->retrieve_logs( 999 ) );
	}

	/**
	 * @test
	 */
	public function it_should_save_logs_in_action_scheduler_format(): void {
		$logger = new ActionScheduler_DB_Logger();
		$task_id = 789;
		$action_id = 1011;
		$context = [
			'task_id'   => $task_id,
			'action_id' => $action_id,
			'type'      => 'started',
			'custom'    => 'data',
		];

		$logger->warning( 'Warning message', $context );

		// Verify the log was saved in the correct format
		$prefix = Config::get_hook_prefix();
		$expected_message_prefix = 'shepherd_' . $prefix . '||' . $task_id . '||started||warning||';
		
		$result = DB::get_row(
			DB::prepare(
				"SELECT * FROM %i WHERE action_id = %d AND message LIKE %s",
				DB::prefix( 'actionscheduler_logs' ),
				$action_id,
				$expected_message_prefix . '%'
			),
			ARRAY_A
		);

		$this->assertNotNull( $result );
		$this->assertStringStartsWith( $expected_message_prefix, $result['message'] );
		
		// Verify the JSON encoded entry
		$message_parts = explode( '||', $result['message'] );
		$json_entry = $message_parts[4] ?? '';
		$decoded = json_decode( $json_entry, true );
		$this->assertEquals( 'Warning message', $decoded['message'] );
		$this->assertEquals( [ 'custom' => 'data' ], $decoded['context'] );
	}

	/**
	 * @test
	 */
	public function it_should_support_all_log_levels(): void {
		$logger = new ActionScheduler_DB_Logger();
		$task_id = 321;
		$base_context = [
			'task_id'   => $task_id,
			'action_id' => 654,
			'type'      => 'created', // Use a valid type
		];

		// Test all valid log levels
		$levels = [
			'emergency' => 'Emergency message',
			'alert'     => 'Alert message',
			'critical'  => 'Critical message',
			'error'     => 'Error message',
			'warning'   => 'Warning message',
			'notice'    => 'Notice message',
			'info'      => 'Info message',
			'debug'     => 'Debug message',
		];

		foreach ( $levels as $level => $message ) {
			$context = array_merge( $base_context, [ 'action_id' => $base_context['action_id'] + array_search( $level, array_keys( $levels ) ) ] );
			$logger->$level( $message, $context );
		}

		$logs = $logger->retrieve_logs( $task_id );
		$this->assertCount( count( $levels ), $logs );

		foreach ( $logs as $index => $log ) {
			$level = array_keys( $levels )[ $index ];
			$this->assertEquals( $level, $log->get_level() );
		}
	}
}