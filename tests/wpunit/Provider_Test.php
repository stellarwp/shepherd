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

		$this->assertNotFalse( has_action( 'action_scheduler_deleted_action', [ $provider, 'delete_tasks_on_action_deletion' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_delete_tasks_on_action_deletion_when_tasks_exist(): void {
		$provider = Config::get_container()->get( Provider::class );
		$shepherd = shepherd();

		// Create one task to get a valid action ID.
		$test_task = new Do_Action_Task();
		$shepherd->dispatch( $test_task );
		$task_id = $shepherd->get_last_scheduled_task_id();
		$action_id = $this->get_task_action_id( $task_id );

		// Create additional tasks with the same action_id directly in the database.
		$additional_task_ids = [];
		for ( $i = 0; $i < 2; $i++ ) {
			DB::query(
				DB::prepare(
					'INSERT INTO %i (action_id, class_hash, args_hash, data, current_try) VALUES (%d, %s, %s, %s, %d)',
					Tasks::table_name(),
					$action_id,
					'test_class_hash_' . $i,
					'test_args_hash_' . $i,
					wp_json_encode( [] ),
					0
				)
			);
			$additional_task_ids[] = $GLOBALS['wpdb']->insert_id;
		}

		$all_task_ids = array_merge( [ $task_id ], $additional_task_ids );

		$tasks_before = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id
			)
		);
		$this->assertEquals( 3, $tasks_before );

		$provider->delete_tasks_on_action_deletion( $action_id );

		$tasks_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id
			)
		);
		$this->assertEquals( 0, $tasks_after );

		$logs_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id IN (%s)',
				Task_Logs::table_name(),
				implode( ',', $all_task_ids )
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

		// Create one task to get a valid action ID.
		$test_task = new Do_Action_Task();
		$shepherd->dispatch( $test_task );
		$task_id = $shepherd->get_last_scheduled_task_id();
		$action_id = $this->get_task_action_id( $task_id );

		// Create additional tasks with the same action_id directly in the database.
		$additional_task_ids = [];
		for ( $i = 0; $i < 2; $i++ ) {
			DB::query(
				DB::prepare(
					'INSERT INTO %i (action_id, class_hash, args_hash, data, current_try) VALUES (%d, %s, %s, %s, %d)',
					Tasks::table_name(),
					$action_id,
					'test_class_hash_' . $i,
					'test_args_hash_' . $i,
					wp_json_encode( [] ),
					0
				)
			);
			$additional_task_ids[] = $GLOBALS['wpdb']->insert_id;
		}

		$all_task_ids = array_merge( [ $task_id ], $additional_task_ids );

		$provider->delete_tasks_on_action_deletion( $action_id );

		$tasks_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				Tasks::table_name(),
				$action_id
			)
		);
		$this->assertEquals( 0, $tasks_after );

		$logs_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE task_id IN (%s)',
				Task_Logs::table_name(),
				implode( ',', $all_task_ids )
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

	/**
	 * @test
	 */
	public function it_should_register_regulator_after_tables_are_registered(): void {
		$prefix = Config::get_hook_prefix();

		$this->assertEquals( 1, did_action( "shepherd_{$prefix}_tables_registered" ), 'Tables registered action should have fired' );

		$this->assertNotFalse(
			has_action( "shepherd_{$prefix}_tables_registered" ),
			'Regulator registration should be hooked to tables_registered action'
		);
	}
}
