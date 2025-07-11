<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
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

class Provider_Test extends WPTestCase {
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

	private function get_provider(): Provider {
		return Config::get_container()->get( Provider::class );
	}

	/**
	 * @test
	 */
	public function it_should_delete_task_data_when_action_is_deleted(): void {
		$shepherd = shepherd();
		$provider = $this->get_provider();
		
		// Create a task
		$task = new Do_Action_Task();
		$shepherd->dispatch( $task );
		$task_id = $shepherd->get_last_scheduled_task_id();
		
		// Execute the task to generate logs
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
		
		$logs_exist = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id = %d',
				Task_Logs::table_name(),
				$task_id
			)
		);
		$this->assertGreaterThan( 0, $logs_exist );
		
		// Get the action ID for this task
		$action_id = $this->get_task_action_id( $task_id );
		
		// Simulate Action Scheduler deleting the action
		$provider->delete_tasks_on_action_deletion( $action_id );
		
		// Verify task was deleted
		$task_exists_after = DB::get_var( 
			DB::prepare( 
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id
			)
		);
		$this->assertEquals( 0, $task_exists_after );
		
		// Verify logs were deleted
		$logs_exist_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id = %d',
				Task_Logs::table_name(),
				$task_id
			)
		);
		$this->assertEquals( 0, $logs_exist_after );
	}

	/**
	 * @test
	 */
	public function it_should_delete_multiple_tasks_for_same_action(): void {
		$shepherd = shepherd();
		$provider = $this->get_provider();
		
		// Create multiple tasks
		$task1 = new Do_Action_Task();
		$shepherd->dispatch( $task1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();
		
		$task2 = new Do_Action_Task();
		$shepherd->dispatch( $task2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();
		
		// Execute both tasks
		$this->assertTaskExecutesWithoutErrors( $task_id_1 );
		$this->assertTaskExecutesWithoutErrors( $task_id_2 );
		
		// Manually set both tasks to have the same action_id for testing
		$common_action_id = 12345;
		DB::query(
			DB::prepare(
				'UPDATE %i SET action_id = %d WHERE %i IN (%d, %d)',
				Tasks::table_name(),
				$common_action_id,
				Tasks::uid_column(),
				$task_id_1,
				$task_id_2
			)
		);
		
		// Verify both tasks exist
		$tasks_count_before = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i IN (%d, %d)',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1,
				$task_id_2
			)
		);
		$this->assertEquals( 2, $tasks_count_before );
		
		// Simulate Action Scheduler deleting the action
		$provider->delete_tasks_on_action_deletion( $common_action_id );
		
		// Verify both tasks were deleted
		$tasks_count_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i IN (%d, %d)',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1,
				$task_id_2
			)
		);
		$this->assertEquals( 0, $tasks_count_after );
		
		// Verify logs for both tasks were deleted
		$logs_count_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id IN (%d, %d)',
				Task_Logs::table_name(),
				$task_id_1,
				$task_id_2
			)
		);
		$this->assertEquals( 0, $logs_count_after );
	}

	/**
	 * @test
	 */
	public function it_should_handle_non_existent_action_id_gracefully(): void {
		$provider = $this->get_provider();
		
		// Try to delete tasks for a non-existent action ID
		$non_existent_action_id = 99999;
		
		// Should not throw any exceptions
		$provider->delete_tasks_on_action_deletion( $non_existent_action_id );
		
		// Test passes if no exception is thrown
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function it_should_only_delete_tasks_for_specified_action(): void {
		$shepherd = shepherd();
		$provider = $this->get_provider();
		
		// Create two tasks
		$task1 = new Do_Action_Task();
		$shepherd->dispatch( $task1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();
		
		$task2 = new Do_Action_Task();
		$shepherd->dispatch( $task2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();
		
		// Execute both tasks
		$this->assertTaskExecutesWithoutErrors( $task_id_1 );
		$this->assertTaskExecutesWithoutErrors( $task_id_2 );
		
		// Get the action ID for the first task
		$action_id_1 = $this->get_task_action_id( $task_id_1 );
		
		// Simulate Action Scheduler deleting only the first action
		$provider->delete_tasks_on_action_deletion( $action_id_1 );
		
		// Verify only the first task was deleted
		$task1_exists = DB::get_var( 
			DB::prepare( 
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1
			)
		);
		$this->assertEquals( 0, $task1_exists );
		
		// Verify the second task still exists
		$task2_exists = DB::get_var( 
			DB::prepare( 
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_2
			)
		);
		$this->assertEquals( 1, $task2_exists );
	}

	/**
	 * @test
	 */
	public function it_should_trigger_on_action_scheduler_delete_hook(): void {
		$provider = $this->get_provider();
		
		// Create a task to have some data
		$shepherd = shepherd();
		$task = new Do_Action_Task();
		$shepherd->dispatch( $task );
		$task_id = $shepherd->get_last_scheduled_task_id();
		$action_id = $this->get_task_action_id( $task_id );
		
		// Track if our method was called
		$method_called = false;
		$called_action_id = null;
		
		// Replace the method with a spy
		$this->set_fn_return( [ $provider, 'delete_tasks_on_action_deletion' ], function( $aid ) use ( &$method_called, &$called_action_id ) {
			$method_called = true;
			$called_action_id = $aid;
		} );
		
		// Fire the action scheduler hook
		do_action( 'action_scheduler_deleted_action', $action_id );
		
		// Verify our method was called with the correct action ID
		$this->assertTrue( $method_called );
		$this->assertEquals( $action_id, $called_action_id );
	}

	/**
	 * Helper method to get action_id for a task
	 */
	private function get_task_action_id( int $task_id ): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT action_id FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id
			)
		);
	}
}