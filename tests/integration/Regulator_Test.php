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
}
