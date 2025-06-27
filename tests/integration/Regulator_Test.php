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
use StellarWP\Pigeon\Tests\Traits\With_Clock_Mock;
use StellarWP\Pigeon\Tests\Traits\With_Uopz;
use Exception;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

use function StellarWP\Pigeon\pigeon;

class Regulator_Test extends WPTestCase {
	use With_AS_Assertions;
	use With_Uopz;
	use With_Clock_Mock;
	use SnapshotAssertions;

	/**
	 * @before
	 * @after
	 */
	public function reset(): void {
		pigeon()->bust_runtime_cached_tasks();
	}

	/**
	 * @before
	 */
	public function freeze(): void {
		$this->freeze_time( tests_pigeon_get_dt() );
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

	/**
	 * @test
	 */
	public function it_should_retry_task_and_mark_as_failed(): void {
		$pigeon = pigeon();
		$this->assertNull( $pigeon->get_last_scheduled_task_id() );

		$dummy_task = new Retryable_Do_Action_Task();
		$pigeon->dispatch( $dummy_task );

		$last_scheduled_task_id = $pigeon->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->set_fn_return( 'do_action', function ( $action, ...$args ) use ( $dummy_task ) {
			if ( $action === $dummy_task->get_task_name() ) {
				throw new Exception( 'Mock Action failure' );
			}

			return do_action( $action, ...$args );
		}, true );

		$this->assertSame( 0, did_action( $dummy_task->get_task_name() ) );

		// 1st try
		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );

		// 2nd try
		$this->assertTaskExecutesFails( $last_scheduled_task_id );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );

		$this->assertCount( 5, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'rescheduled', $logs[2]->get_type() );
		$this->assertSame( 'retrying', $logs[3]->get_type() );
		$this->assertSame( 'failed', $logs[4]->get_type() );

		$log_array = array_map(
			fn( Log $log ) => $log->to_array(),
			$logs
		);

		$action_ids = array_values(
			array_unique(
				array_map(
					fn( array $log ) => json_decode( $log['entry'], true )['context']['action_id'],
					$log_array
				)
			)
		);

		$previous_action_ids = array_values(
			array_filter(
				array_unique(
					array_map(
						fn( array $log ) => json_decode( $log['entry'], true )['context']['previous_action_id'] ?? null,
						$log_array
					)
				)
			)
		);

		$json = wp_json_encode( $log_array, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace(
			wp_list_pluck( $log_array, 'id' ),
			[ '{LOG_ID_1}', '{LOG_ID_2}', '{LOG_ID_3}', '{LOG_ID_4}', '{LOG_ID_5}' ],
			$json
		);

		$action_placeholders = array_fill( 0, count( $action_ids ), '{ACTION_ID_' );

		foreach( $action_placeholders as $key => $placeholder ) {
			$json = str_replace(
				$action_ids[$key],
				$placeholder . ($key + 1) . '}',
				$json
			);
		}

		$previous_action_placeholders = array_fill( 0, count( $previous_action_ids ), '{PREVIOUS_ACTION_ID_' );

		foreach( $previous_action_placeholders as $key => $placeholder ) {
			$json = str_replace(
				$previous_action_ids[$key],
				$placeholder . ($key + 1) . '}',
				$json
			);
		}

		$json = str_replace(
			(string) $last_scheduled_task_id,
			'{TASK_ID}',
			$json
		);

		$this->assertMatchesJsonSnapshot( $json );
	}
}
