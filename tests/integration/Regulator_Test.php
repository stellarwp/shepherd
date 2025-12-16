<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;
use StellarWP\Shepherd\Provider;
use StellarWP\Shepherd\Tests\Tasks\Do_Action_Task;
use StellarWP\Shepherd\Tests\Tasks\Always_Fail_Task;
use StellarWP\Shepherd\Tests\Tasks\Do_Prefixed_Action_Task;
use StellarWP\Shepherd\Tests\Tasks\Retryable_Do_Action_Task;
use StellarWP\Shepherd\Tests\Tasks\Internal_Counting_Task;
use StellarWP\Shepherd\Tests\Traits\With_AS_Assertions;
use StellarWP\Shepherd\Tests\Traits\With_Clock_Mock;
use StellarWP\Shepherd\Tests\Traits\With_Log_Snapshot;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use Exception;

use function StellarWP\Shepherd\shepherd;

class Regulator_Test extends WPTestCase {
	use With_AS_Assertions;
	use With_Uopz;
	use With_Clock_Mock;
	use With_Log_Snapshot;

	/**
	 * @before
	 * @after
	 */
	public function reset(): void {
		shepherd()->bust_runtime_cached_tasks();
	}

	/**
	 * @before
	 */
	public function freeze(): void {
		$this->freeze_time( tests_shepherd_get_dt() );
	}

	private function get_logger(): Logger {
		return Config::get_container()->get( Logger::class );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_and_process_task_without_args(): void {
		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$dummy_task = new Do_Action_Task();
		$shepherd->dispatch( $dummy_task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 1, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertSame( 0, did_action( $dummy_task->get_task_name() ) );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertSame( 1, did_action( $dummy_task->get_task_name() ) );

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
	public function it_should_schedule_same_task_only_once(): void {
		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$dummy_task = new Internal_Counting_Task();

		$this->assertSame( 0, did_action( 'shepherd_' . tests_shepherd_get_hook_prefix() . '_task_already_scheduled' ) );
		$shepherd->dispatch( $dummy_task );
		$this->assertSame( 0, did_action( 'shepherd_' . tests_shepherd_get_hook_prefix() . '_task_already_scheduled' ) );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$shepherd->dispatch( $dummy_task );
		$this->assertSame( 1, did_action( 'shepherd_' . tests_shepherd_get_hook_prefix() . '_task_already_scheduled' ) );
		$this->assertEquals( $shepherd->get_last_scheduled_task_id(), $last_scheduled_task_id );

		$shepherd->dispatch( new Internal_Counting_Task() );
		$this->assertSame( 2, did_action( 'shepherd_' . tests_shepherd_get_hook_prefix() . '_task_already_scheduled' ) );
		$this->assertEquals( $shepherd->get_last_scheduled_task_id(), $last_scheduled_task_id );

		$this->assertSame( 0, did_action( $dummy_task->get_task_name() ) );

		$this->assertSame( 0, Internal_Counting_Task::$processed );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertSame( 1, Internal_Counting_Task::$processed );

		$this->assertSame( 1, did_action( $dummy_task->get_task_name() ) );

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
	public function it_should_schedule_and_process_task_with_args(): void {
		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$dummy_task = new Do_Prefixed_Action_Task( 'dimi' );

		$shepherd->dispatch( $dummy_task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 1, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertSame( 0, did_action( $dummy_task->get_task_name() ) );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertSame( 1, did_action( $dummy_task->get_task_name() ) );

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
	public function it_should_retry_task_and_mark_as_failed(): void {
		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$dummy_task = new Always_Fail_Task( 'arg1', 'arg2', 55, 44 );
		$shepherd->dispatch( $dummy_task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		// 1st try
		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 30 );

		// 2nd try
		$this->assertTaskExecutesFails( $last_scheduled_task_id );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );

		$this->assertCount( 5, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'rescheduled', $logs[2]->get_type() );
		$this->assertSame( 'retrying', $logs[3]->get_type() );
		$this->assertSame( 'failed', $logs[4]->get_type() );

		$this->assertMatchesLogSnapshot( $logs );
	}

	/**
	 * @test
	 */
	public function it_should_retry_task_and_succeed(): void {
		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$dummy_task = new Retryable_Do_Action_Task();
		$shepherd->dispatch( $dummy_task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertSame( 0, did_action( $dummy_task->get_task_name() ) );

		// 1st try
		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() + 30 );

		$this->assertSame( 0, did_action( $dummy_task->get_task_name() ) );

		$this->unset_uopz_returns();

		// 2nd try
		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertSame( 1, did_action( $dummy_task->get_task_name() ) );

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
	public function it_should_run_tasks_and_log_lifecycle(): void {
		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$task1 = new Do_Prefixed_Action_Task( 'run_log_1' );
		$task2 = new Do_Prefixed_Action_Task( 'run_log_2' );

		$this->assertSame( 0, did_action( $task1->get_task_name() ) );
		$this->assertSame( 0, did_action( $task2->get_task_name() ) );

		$shepherd->run( [ $task1, $task2 ] );

		$this->assertSame( 1, did_action( $task1->get_task_name() ) );
		$this->assertSame( 1, did_action( $task2->get_task_name() ) );

		// Verify logs for task1
		$logs1 = $this->get_logger()->retrieve_logs( $task1->get_id() );
		$this->assertCount( 3, $logs1 );
		$this->assertSame( 'created', $logs1[0]->get_type() );
		$this->assertSame( 'started', $logs1[1]->get_type() );
		$this->assertSame( 'finished', $logs1[2]->get_type() );

		// Verify logs for task2
		$logs2 = $this->get_logger()->retrieve_logs( $task2->get_id() );
		$this->assertCount( 3, $logs2 );
		$this->assertSame( 'created', $logs2[0]->get_type() );
		$this->assertSame( 'started', $logs2[1]->get_type() );
		$this->assertSame( 'finished', $logs2[2]->get_type() );
	}

	/**
	 * @test
	 */
	public function it_should_run_tasks_and_call_on_error_when_task_fails(): void {
		$shepherd = shepherd();

		$task1 = new Do_Prefixed_Action_Task( 'run_error_1' );
		$task2 = new Always_Fail_Task( 'run_error_2' );
		$task3 = new Do_Prefixed_Action_Task( 'run_error_3' );

		$error_task = null;
		$error_exception = null;
		$always_called = false;
		$always_tasks = null;

		$prefix = tests_shepherd_get_hook_prefix();
		$run_failed_count = did_action( "shepherd_{$prefix}_tasks_run_failed" );

		$shepherd->run( [ $task1, $task2, $task3 ], [
			'on_error' => function( $task, $e ) use ( &$error_task, &$error_exception ) {
				$error_task = $task;
				$error_exception = $e;
			},
			'always' => function( $tasks ) use ( &$always_called, &$always_tasks ) {
				$always_called = true;
				$always_tasks = $tasks;
			},
		] );

		// First task should have run
		$this->assertSame( 1, did_action( $task1->get_task_name() ) );

		// Error callback should have been called
		$this->assertNotNull( $error_task );
		$this->assertNotNull( $error_exception );
		$this->assertInstanceOf( Exception::class, $error_exception );

		// Always callback should have been called even on error
		$this->assertTrue( $always_called, 'always callable should be called even when tasks fail' );

		// tasks_run_failed action should have fired
		$this->assertSame( $run_failed_count + 1, did_action( "shepherd_{$prefix}_tasks_run_failed" ) );
	}

	/**
	 * @test
	 */
	public function it_should_run_task_that_was_previously_dispatched(): void {
		$shepherd = shepherd();

		$task = new Do_Prefixed_Action_Task( 'run_dispatched' );

		// First dispatch the task normally
		$shepherd->dispatch( $task );
		$task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertSame( 0, did_action( $task->get_task_name() ) );

		// Now run it - should use the already dispatched task
		$shepherd->run( [ $task ] );

		$this->assertSame( 1, did_action( $task->get_task_name() ) );

		// Verify the logs show the full lifecycle
		$logs = $this->get_logger()->retrieve_logs( $task_id );
		$this->assertCount( 3, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'finished', $logs[2]->get_type() );
	}

	/**
	 * @test
	 */
	public function it_should_run_single_task_successfully(): void {
		$shepherd = shepherd();

		$task = new Do_Action_Task();

		$before_called = false;
		$after_called = false;
		$always_called = false;

		$shepherd->run( [ $task ], [
			'before' => function() use ( &$before_called ) {
				$before_called = true;
			},
			'after' => function() use ( &$after_called ) {
				$after_called = true;
			},
			'always' => function() use ( &$always_called ) {
				$always_called = true;
			},
		] );

		$this->assertSame( 1, did_action( $task->get_task_name() ) );
		$this->assertTrue( $before_called, 'before callable should have been called' );
		$this->assertTrue( $after_called, 'after callable should have been called' );
		$this->assertTrue( $always_called, 'always callable should have been called' );
	}
}
