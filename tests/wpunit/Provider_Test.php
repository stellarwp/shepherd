<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\Shepherd\Tests\Tasks\Do_Action_Task;
use StellarWP\DB\DB;

class Provider_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @test
	 */
	public function it_should_assert_that_the_provider_is_not_registered(): void {
		$this->assertTrue( Provider::is_registered() );
	}

	/**
	 * @test
	 */
	public function it_should_evaluate_hook_prefix(): void {
		$this->assertEquals( tests_shepherd_get_hook_prefix(), Config::get_hook_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_register_action_deletion_hook(): void {
		$provider = Config::get_container()->get( Provider::class );
		
		$this->assertTrue( has_action( 'action_scheduler_deleted_action', [ $provider, 'delete_tasks_on_action_deletion' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_delete_tasks_on_action_deletion_when_tasks_exist(): void {
		$provider = Config::get_container()->get( Provider::class );
		$shepherd = shepherd();
		
		// Create real tasks using Shepherd's API.
		$test_task_1 = new Do_Action_Task();
		$shepherd->dispatch( $test_task_1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();
		
		$test_task_2 = new Do_Action_Task();
		$shepherd->dispatch( $test_task_2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();
		
		$test_task_3 = new Do_Action_Task();
		$shepherd->dispatch( $test_task_3 );
		$task_id_3 = $shepherd->get_last_scheduled_task_id();
		
		// Get actual action IDs from the tasks.
		$action_id_1 = $this->get_task_action_id( $task_id_1 );
		$action_id_2 = $this->get_task_action_id( $task_id_2 );
		$action_id_3 = $this->get_task_action_id( $task_id_3 );
		
		// Set them all to the same action ID for testing.
		DB::query(
			DB::prepare(
				'UPDATE %i SET action_id = %d WHERE %i IN (%d, %d, %d)',
				Tasks::table_name(),
				$action_id_1,
				Tasks::uid_column(),
				$task_id_1,
				$task_id_2,
				$task_id_3
			)
		);
		
		$tasks_before = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id_1
			)
		);
		$this->assertEquals( 3, $tasks_before );
		
		$provider->delete_tasks_on_action_deletion( $action_id_1 );
		
		$tasks_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id_1
			)
		);
		$this->assertEquals( 0, $tasks_after );
		
		$logs_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id IN (%d, %d, %d)',
				Task_Logs::table_name(),
				$task_id_1,
				$task_id_2,
				$task_id_3
			)
		);
		$this->assertEquals( 0, $logs_after );
	}

	/**
	 * @test
	 */
	public function it_should_not_delete_when_no_tasks_exist_for_action(): void {
		$provider = Config::get_container()->get( Provider::class );
		$action_id = 456;
		
		$tasks_before = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id
			)
		);
		$this->assertEquals( 0, $tasks_before );
		
		$provider->delete_tasks_on_action_deletion( $action_id );
		
		$tasks_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id
			)
		);
		$this->assertEquals( 0, $tasks_after );
	}

	/**
	 * @test
	 */
	public function it_should_sanitize_task_ids_before_deletion(): void {
		$provider = Config::get_container()->get( Provider::class );
		$shepherd = shepherd();
		
		// Create real tasks using Shepherd's API.
		$test_task_1 = new Do_Action_Task();
		$shepherd->dispatch( $test_task_1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();
		
		$test_task_2 = new Do_Action_Task();
		$shepherd->dispatch( $test_task_2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();
		
		$test_task_3 = new Do_Action_Task();
		$shepherd->dispatch( $test_task_3 );
		$task_id_3 = $shepherd->get_last_scheduled_task_id();
		
		// Get first task's action ID to use for all.
		$common_action_id = $this->get_task_action_id( $task_id_1 );
		
		// Update all tasks to have the same action_id to test deduplication.
		DB::query(
			DB::prepare(
				'UPDATE %i SET action_id = %d WHERE %i IN (%d, %d, %d)',
				Tasks::table_name(),
				$common_action_id,
				Tasks::uid_column(),
				$task_id_1,
				$task_id_2,
				$task_id_3
			)
		);
		
		$provider->delete_tasks_on_action_deletion( $common_action_id );
		
		$tasks_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$common_action_id
			)
		);
		$this->assertEquals( 0, $tasks_after );
		
		$logs_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id IN (%d, %d, %d)',
				Task_Logs::table_name(),
				$task_id_1,
				$task_id_2,
				$task_id_3
			)
		);
		$this->assertEquals( 0, $logs_after );
	}

	/**
	 * Helper method to get action_id for a task.
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
