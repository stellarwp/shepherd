<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tests\Tasks\Do_Action_Task;
use StellarWP\Shepherd\Tests\Traits\With_AS_Assertions;
use StellarWP\Shepherd\Tests\Traits\With_Clock_Mock;
use StellarWP\Shepherd\Tests\Traits\With_Log_Snapshot;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\DB\DB;

use function StellarWP\Shepherd\shepherd;

class Herding_Test extends WPTestCase {
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
	public function it_should_process_herding_task_and_clean_orphaned_data(): void {
		$shepherd = shepherd();

		// Create a regular task first
		$dummy_task = new Do_Action_Task();
		$shepherd->dispatch( $dummy_task );
		$task_id = $shepherd->get_last_scheduled_task_id();

		// Execute the task so it has logs
		$this->assertTaskExecutesWithoutErrors( $task_id );

		// Verify task and logs exist
		$task_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id
			)
		);
		$this->assertEquals( 1, $task_exists );

		$logs_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id );
		$this->assertCount( 3, $logs_exist );

		// Now manually remove the action from Action Scheduler to simulate orphaned data
		DB::query(
			DB::prepare(
				'DELETE FROM %i WHERE action_id = %d',
				DB::prefix( 'actionscheduler_actions' ),
				$dummy_task->get_action_id()
			)
		);

		// Dispatch herding task
		$herding_task = new Herding();
		$shepherd->dispatch( $herding_task );
		$herding_task_id = $shepherd->get_last_scheduled_task_id();

		$hook_name = 'shepherd_' . tests_shepherd_get_hook_prefix() . '_herding_processed';

		$this->assertSame( 0 , did_action( $hook_name ) );

		// Execute herding task
		$this->assertTaskExecutesWithoutErrors( $herding_task_id );

		$this->assertSame( 1 , did_action( $hook_name ) );

		// Verify orphaned task was cleaned up
		$task_exists_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id
			)
		);
		$this->assertEquals( 0, $task_exists_after );

		// Verify orphaned logs were cleaned up
		$logs_exist_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id = %d',
				Task_Logs::table_name(),
				$task_id
			)
		);
		$this->assertEquals( 0, $logs_exist_after );

		// Verify herding task itself still exists (it's not orphaned)
		$herding_task_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$herding_task_id
			)
		);
		$this->assertEquals( 1, $herding_task_exists );
	}

	/**
	 * @test
	 */
	public function it_should_process_herding_task_and_clean_orphaned_data_in_batches(): void {
		$shepherd = shepherd();

		$task_1 = new Do_Action_Task( 'arg1');
		$shepherd->dispatch( $task_1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $task_id_1 );

		$task_2 = new Do_Action_Task( 'arg2');
		$shepherd->dispatch( $task_2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();

		$task_3 = new Do_Action_Task( 'arg3');
		$shepherd->dispatch( $task_3 );
		$task_id_3 = $shepherd->get_last_scheduled_task_id();

		// Verify task and logs exist
		$task_1_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1
			)
		);
		$this->assertEquals( 1, $task_1_exists );

		$logs_1_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_1 );
		$this->assertCount( 3, $logs_1_exist );

		$logs_2_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_2 );
		$this->assertCount( 1, $logs_2_exist );

		$logs_3_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_3 );
		$this->assertCount( 1, $logs_3_exist );

		DB::query(
			DB::prepare(
				'DELETE FROM %i WHERE action_id IN (%d, %d)',
				DB::prefix( 'actionscheduler_actions' ),
				$task_1->get_action_id(),
				$task_2->get_action_id()
			)
		);

		add_filter( 'shepherd_' . tests_shepherd_get_hook_prefix() . '_herding_batch_limit', function () {
			return 1;
		} );

		// Dispatch herding task
		$herding_task = new Herding();
		$shepherd->dispatch( $herding_task );
		$herding_task_id = $shepherd->get_last_scheduled_task_id();

		$hook_name = 'shepherd_' . tests_shepherd_get_hook_prefix() . '_herding_processed';

		$this->assertSame( 0 , did_action( $hook_name ) );

		// Execute herding task
		$this->assertTaskExecutesWithoutErrors( $herding_task_id );

		$this->assertSame( 1 , did_action( $hook_name ) );

		$task_1_exists_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1
			)
		);
		$this->assertEquals( 0, $task_1_exists_after );

		$task_2_exists_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_2
			)
		);

		$this->assertEquals( 0, $task_2_exists_after );

		$task_3_exists_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_3
			)
		);
		$this->assertEquals( 1, $task_3_exists_after );

		$logs_1_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_1 );
		$this->assertCount( 0, $logs_1_exist );

		$logs_2_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_2 );
		$this->assertCount( 0, $logs_2_exist );

		$logs_3_exist = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_3 );
		$this->assertCount( 1, $logs_3_exist );
	}

	/**
	 * @test
	 */
	public function it_should_handle_no_orphaned_data_gracefully(): void {
		$shepherd = shepherd();

		// Dispatch herding task when no orphaned data exists
		$herding_task = new Herding();
		$shepherd->dispatch( $herding_task );
		$herding_task_id = $shepherd->get_last_scheduled_task_id();

		// Should execute without errors
		$this->assertTaskExecutesWithoutErrors( $herding_task_id );

		// Verify logs show successful completion
		$logs = $this->get_logger()->retrieve_logs( $herding_task_id );
		$this->assertCount( 3, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'finished', $logs[2]->get_type() );
	}

	/**
	 * @test
	 */
	public function it_should_only_clean_orphaned_tasks_not_active_ones(): void {
		$shepherd = shepherd();

		// Create multiple tasks
		$task1 = new Do_Action_Task( 'arg1' );
		$shepherd->dispatch( $task1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();

		// Execute first task
		$this->assertTaskExecutesWithoutErrors( $task_id_1 );

		$task2 = new Do_Action_Task( 'arg2' );
		$shepherd->dispatch( $task2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();

		$this->assertNotEquals( $task_id_1, $task_id_2 );

		// Manually remove only the first task's action from Action Scheduler
		DB::query(
			DB::prepare(
				'DELETE FROM %i WHERE action_id = %d',
				DB::prefix( 'actionscheduler_actions' ),
				$task1->get_action_id()
			)
		);

		// Dispatch and execute herding task
		$herding_task = new Herding();
		$shepherd->dispatch( $herding_task );
		$herding_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $herding_task_id );

		// Verify only the orphaned task was cleaned up
		$task1_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1
			)
		);
		$this->assertEquals( 0, $task1_exists, 'Orphaned task should be cleaned up' );

		// Verify the active task still exists
		$task2_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_2
			)
		);
		$this->assertEquals( 1, $task2_exists, 'Active task should remain' );
	}
}
