<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;
use StellarWP\Pigeon\Provider;
use StellarWP\Pigeon\Tests\Tasks\Do_Action_Task;
use StellarWP\Pigeon\Tests\Tasks\Do_Prefixed_Action_Task;
use StellarWP\Pigeon\Tests\Tasks\Retryable_Do_Action_Task;
use StellarWP\Pigeon\Tests\Traits\With_AS_Assertions;
use function StellarWP\Pigeon\pigeon;

class Regulator_Test extends WPTestCase {
	use With_AS_Assertions;

	/**
	 * @before
	 * @after
	 */
	public function reset(): void {
		pigeon()->bust_runtime_cached_tasks();
	}

	private function get_logger(): DB_Logger {
		return Provider::get_container()->get( Logger::class );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_and_process_task_without_args(): void {
		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Do_Action_Task();
		$pigeon->dispatch( $dummy_task );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();

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
	}

	/**
	 * @test
	 */
	public function it_should_schedule_and_process_task_with_args(): void {
		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Do_Prefixed_Action_Task( 'dimi' );

		$pigeon->dispatch( $dummy_task );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();

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
	}
}
