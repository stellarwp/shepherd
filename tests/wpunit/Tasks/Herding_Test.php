<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\DB\DB;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;

class Herding_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @before
	 */
	public function set_logger(): void {
		Config::set_logger( new DB_Logger() );
		Config::get_container()->singleton( Logger::class, Config::get_logger() );
	}

	/**
	 * @after
	 */
	public function reset_logger(): void {
		Config::set_logger( new ActionScheduler_DB_Logger() );
		Config::get_container()->singleton( Logger::class, Config::get_logger() );
	}

	/**
	 * @test
	 */
	public function it_should_return_correct_task_prefix() {
		$herding = new Herding();
		$this->assertEquals( 'shepherd_tidy_', $herding->get_task_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_delete_orphaned_tasks_and_logs() {
		$herding = new Herding();

		// Clear existing data
		DB::query( DB::prepare( 'DELETE FROM %i', Tasks::table_name() ) );
		DB::query( DB::prepare( 'DELETE FROM %i', Task_Logs::table_name() ) );

		// Create orphaned tasks (tasks without corresponding actions in Action Scheduler)
		$orphaned_task_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			DB::query(
				DB::prepare(
					'INSERT INTO %i (action_id, class_hash, args_hash, data, current_try) VALUES (%d, %s, %s, %s, %d)',
					Tasks::table_name(),
					999990 + $i, // Use high action IDs that won't exist in Action Scheduler
					'test_class_hash_' . $i,
					'test_args_hash_' . $i,
					wp_json_encode( [] ),
					0
				)
			);
			$orphaned_task_ids[] = $GLOBALS['wpdb']->insert_id;

			// Create logs for these tasks
			DB::query(
				DB::prepare(
					'INSERT INTO %i (task_id, date, level, type, entry) VALUES (%d, %s, %s, %s, %s)',
					Task_Logs::table_name(),
					$GLOBALS['wpdb']->insert_id,
					gmdate( 'Y-m-d H:i:s' ),
					'info',
					'test',
					wp_json_encode( [ 'message' => 'Test log entry' ] )
				)
			);
		}

		// Verify tasks and logs exist before cleanup
		$tasks_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$logs_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$this->assertEquals( 3, $tasks_before );
		$this->assertEquals( 3, $logs_before );

		$herding->process();

		// Verify tasks and logs were deleted
		$tasks_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$logs_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$this->assertEquals( 0, $tasks_after );
		$this->assertEquals( 0, $logs_after );
	}

	/**
	 * @test
	 */
	public function it_should_fire_action_hook_after_processing() {
		$herding = new Herding();

		// Clear existing data
		DB::query( DB::prepare( 'DELETE FROM %i', Tasks::table_name() ) );
		DB::query( DB::prepare( 'DELETE FROM %i', Task_Logs::table_name() ) );

		// Track action hook calls
		$hook_called = false;
		$hook_task = null;

		add_action( 'shepherd_' . tests_shepherd_get_hook_prefix() . '_herding_processed', function( $task ) use ( &$hook_called, &$hook_task ) {
			$hook_called = true;
			$hook_task = $task;
		} );

		$herding->process();

		$this->assertTrue( $hook_called );
		$this->assertSame( $herding, $hook_task );
	}

	/**
	 * @test
	 */
	public function it_should_sanitize_task_ids_before_deletion() {
		$herding = new Herding();

		// Clear existing data
		DB::query( DB::prepare( 'DELETE FROM %i', Tasks::table_name() ) );
		DB::query( DB::prepare( 'DELETE FROM %i', Task_Logs::table_name() ) );

		// Create orphaned tasks with high action IDs that won't exist in Action Scheduler
		$orphaned_task_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			DB::query(
				DB::prepare(
					'INSERT INTO %i (action_id, class_hash, args_hash, data, current_try) VALUES (%d, %s, %s, %s, %d)',
					Tasks::table_name(),
					999990 + $i,
					'test_class_hash_' . $i,
					'test_args_hash_' . $i,
					wp_json_encode( [] ),
					0
				)
			);
			$orphaned_task_ids[] = $GLOBALS['wpdb']->insert_id;
		}

		// Verify tasks exist before cleanup
		$tasks_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$this->assertEquals( 3, $tasks_before );

		$herding->process();

		// Verify all tasks were deleted (testing sanitization worked)
		$tasks_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$this->assertEquals( 0, $tasks_after );
	}
}
