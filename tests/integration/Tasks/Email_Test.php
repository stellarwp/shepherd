<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;
use StellarWP\Pigeon\Provider;
use StellarWP\Pigeon\Tests\Traits\With_AS_Assertions;
use StellarWP\Pigeon\Tests\Traits\With_Clock_Mock;
use StellarWP\Pigeon\Tests\Traits\With_Log_Snapshot;
use StellarWP\Pigeon\Tests\Traits\With_Uopz;

use function StellarWP\Pigeon\pigeon;

class Email_Test extends WPTestCase {
	use With_AS_Assertions;
	use With_Uopz;
	use With_Clock_Mock;
	use With_Log_Snapshot;

	/**
	 * @before
	 */
	public function setup(): void {
		$this->freeze_time( tests_pigeon_get_dt() );
		pigeon()->bust_runtime_cached_tasks();
	}

	private function get_logger(): DB_Logger {
		return Provider::get_container()->get( Logger::class );
	}

	/**
	 * @test
	 */
	public function it_should_dispatch_and_process_email(): void {
		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( ...$args ) use ( &$spy ) {
			$spy[] = $args;
			return true;
		}, true );

		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Email( 'test@test.com', 'subject', 'body', [ 'Reply-To: test@test.com' ], [ 'attachment.txt' ] );
		$pigeon->dispatch( $dummy_task );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertSame( [ 'test@test.com', 'subject', 'body', [ 'Reply-To: test@test.com' ], [ 'attachment.txt' ] ], $spy[0] );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 3, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'finished', $logs[2]->get_type() );

		$this->assertMatchesLogSnapshot( $logs );
	}

	/**
	 * @test
	 */
	public function it_should_try_a_total_of_5_times(): void {
		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( ...$args ) use ( &$spy ) {
			$spy[] = $args;
			return false;
		}, true );

		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Email( 'test@test.com', 'subject', 'body', [ 'Reply-To: dimi@test.com' ], [ 'attachment.jpg' ] );
		$pigeon->dispatch( $dummy_task );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 30 );

		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 60 );

		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 120 );

		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 240 );

		$this->assertTaskExecutesFails( $last_scheduled_task_id );

		$this->assertCount( 5, $spy );
		foreach ( $spy as $call ) {
			$this->assertSame( [ 'test@test.com', 'subject', 'body', [ 'Reply-To: dimi@test.com' ], [ 'attachment.jpg' ] ], $call );
		}

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 11, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'rescheduled', $logs[2]->get_type() );
		$this->assertSame( 'retrying', $logs[3]->get_type() );
		$this->assertSame( 'rescheduled', $logs[4]->get_type() );
		$this->assertSame( 'retrying', $logs[5]->get_type() );
		$this->assertSame( 'rescheduled', $logs[6]->get_type() );
		$this->assertSame( 'retrying', $logs[7]->get_type() );
		$this->assertSame( 'rescheduled', $logs[8]->get_type() );
		$this->assertSame( 'retrying', $logs[9]->get_type() );
		$this->assertSame( 'failed', $logs[10]->get_type() );

		$this->assertMatchesLogSnapshot( $logs );
	}

	/**
	 * @test
	 */
	public function it_should_retry_and_succeed_email(): void {
		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( ...$args ) use ( &$spy ) {
			$spy[] = $args;
			return false;
		}, true );

		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Email( 'test@test.com', 'subject', 'body' );
		$pigeon->dispatch( $dummy_task );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 30 );

		$this->set_fn_return( 'wp_mail', function ( ...$args ) use ( &$spy ) {
			$spy[] = $args;
			return true;
		}, true );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 2, $spy );
		$this->assertSame( [ 'test@test.com', 'subject', 'body', [], [] ], $spy[0] );
		$this->assertSame( [ 'test@test.com', 'subject', 'body', [], [] ], $spy[1] );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 5, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'rescheduled', $logs[2]->get_type() );
		$this->assertSame( 'retrying', $logs[3]->get_type() );
		$this->assertSame( 'finished', $logs[4]->get_type() );

		$this->assertMatchesLogSnapshot( $logs );
	}

	/**
	 * @test
	 */
	public function it_should_not_schedule_same_task_twice(): void {
		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( ...$args ) use ( &$spy ) {
			$spy[] = $args;
			return true;
		}, true );

		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Email( 'test@test.com', 'subject', 'body' );

		$hook_name = 'pigeon_' . tests_pigeon_get_hook_prefix() . '_task_already_scheduled';

		$this->assertSame( 0, did_action( $hook_name ) );
		$pigeon->dispatch( $dummy_task );
		$this->assertSame( 0, did_action( $hook_name ) );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();
		$this->assertIsInt( $last_scheduled_task_id );

		$pigeon->dispatch( $dummy_task );
		$this->assertSame( 1, did_action( $hook_name ) );
		$this->assertEquals( $pigeon->get_last_scheduled_task_id(), $last_scheduled_task_id );

		$pigeon->dispatch( new Email( 'test@test.com', 'subject', 'body' ) );
		$this->assertSame( 2, did_action( $hook_name ) );
		$this->assertEquals( $pigeon->get_last_scheduled_task_id(), $last_scheduled_task_id );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertSame( [ 'test@test.com', 'subject', 'body', [], [] ], $spy[0] );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_multiple_tasks_with_different_args(): void {
		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( ...$args ) use ( &$spy ) {
			$spy[] = $args;
			return true;
		}, true );

		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$task1 = new Email( 'test1@test.com', 'subject1', 'body1' );
		$pigeon->dispatch( $task1 );
		$task1_id = $pigeon->get_last_scheduled_task_id();
		$this->assertIsInt( $task1_id );

		$task2 = new Email( 'test2@test.com', 'subject2', 'body2' );
		$pigeon->dispatch( $task2 );
		$task2_id = $pigeon->get_last_scheduled_task_id();
		$this->assertIsInt( $task2_id );

		$this->assertNotEquals( $task1_id, $task2_id );

		$hook_name = 'pigeon_' . tests_pigeon_get_hook_prefix() . '_task_already_scheduled';
		$this->assertSame( 0, did_action( $hook_name ) );

		$this->assertTaskExecutesWithoutErrors( $task1_id );
		$this->assertTaskExecutesWithoutErrors( $task2_id );

		$this->assertCount( 2, $spy );
		$this->assertSame( [ 'test1@test.com', 'subject1', 'body1', [], [] ], $spy[0] );
		$this->assertSame( [ 'test2@test.com', 'subject2', 'body2', [], [] ], $spy[1] );
	}
}
