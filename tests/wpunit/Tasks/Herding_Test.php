<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tests\Traits\With_AS_Assertions;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\DB\DB;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Tests\Tasks\Do_Action_Task;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;
use function StellarWP\Shepherd\shepherd;

class Herding_Test extends WPTestCase {
	use With_Uopz;
	use With_AS_Assertions;

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
		$logger = Config::get_container()->get( Logger::class );

		// Create tasks and then delete their actions to make them orphaned
		$task_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$test_task = new Do_Action_Task( 'test_arg_' . $i );
			shepherd()->dispatch( $test_task );
			$task_ids[] = $test_task->get_id();

			$logger->log( 'info', 'Test log entry ' . $i, [ 'task_id' => $test_task->get_id(), 'type' => 'cancelled', 'action_id' => $test_task->get_action_id() ] );

			DB::query( DB::prepare( 'DELETE FROM %i WHERE action_id = %d', DB::prefix( 'actionscheduler_actions' ), $test_task->get_action_id() ) );
		}

		$tasks_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$logs_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$this->assertEquals( 3, $tasks_before );
		$this->assertEquals( 6, $logs_before );

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

		// Create tasks and then delete their actions to make them orphaned
		$task_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$test_task = new Do_Action_Task( 'sanitize_test_' . $i );
			shepherd()->dispatch( $test_task );
			$task_ids[] = $test_task->get_id();

			DB::query( DB::prepare( 'DELETE FROM %i WHERE action_id = %d', DB::prefix( 'actionscheduler_actions' ), $test_task->get_action_id() ) );
		}

		$tasks_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$this->assertEquals( 3, $tasks_before );

		$herding->process();

		$tasks_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$this->assertEquals( 0, $tasks_after );
	}

	/**
	 * @test
	 */
	public function it_should_delete_task_data_with_db_logger() {
		Config::set_logger( new DB_Logger() );
		Config::get_container()->singleton( Logger::class, Config::get_logger() );
		$logger = Config::get_container()->get( Logger::class );

		$task_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$test_task = new Do_Action_Task( 'delete_test_' . $i );
			shepherd()->dispatch( $test_task );
			$task_ids[] = $test_task->get_id();

			$logger->log( 'info', 'Test log ' . $i, [ 'task_id' => $test_task->get_id(), 'type' => 'cancelled', 'action_id' => $test_task->get_action_id() ] );
		}

		$tasks_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$logs_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$this->assertEquals( 3, $tasks_before );
		$this->assertEquals( 6, $logs_before );

		Herding::delete_data_of_tasks( $task_ids );

		$tasks_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$logs_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$this->assertEquals( 0, $tasks_after );
		$this->assertEquals( 0, $logs_after );
	}

	/**
	 * @test
	 */
	public function it_should_delete_task_data_with_actionscheduler_logger() {
		Config::set_logger( new ActionScheduler_DB_Logger() );
		Config::get_container()->singleton( Logger::class, Config::get_logger() );
		$logger = Config::get_container()->get( Logger::class );

		$task_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$test_task = new Do_Action_Task( 'as_logger_test_' . $i );
			shepherd()->dispatch( $test_task );
			$task_ids[] = $test_task->get_id();

			$logger->log( 'info', 'Test log ' . $i, [ 'task_id' => $test_task->get_id(), 'type' => 'cancelled', 'action_id' => $test_task->get_action_id() ] );
		}

		$tasks_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$task_logs_before = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$as_logs_before = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE message LIKE %s',
				DB::prefix( 'actionscheduler_logs' ),
				'shepherd_' . Config::get_hook_prefix() . '||%'
			)
		);
		$this->assertEquals( 3, $tasks_before );
		$this->assertEquals( 0, $task_logs_before );
		$this->assertGreaterThanOrEqual( 3, $as_logs_before );

		Herding::delete_data_of_tasks( $task_ids );

		$tasks_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Tasks::table_name() ) );
		$this->assertEquals( 0, $tasks_after );

		$task_logs_after = DB::get_var( DB::prepare( 'SELECT COUNT(*) FROM %i', Task_Logs::table_name() ) );
		$this->assertEquals( 0, $task_logs_after, 'Task_Logs should not be deleted when using ActionScheduler logger' );

		$as_logs_after = 0;
		foreach ( $task_ids as $task_id ) {
			$count = DB::get_var(
				DB::prepare(
					'SELECT COUNT(*) FROM %i WHERE message LIKE %s',
					DB::prefix( 'actionscheduler_logs' ),
					'shepherd_' . Config::get_hook_prefix() . '||' . $task_id . '||%'
				)
			);
			$as_logs_after += $count;
		}
		$this->assertEquals( 0, $as_logs_after, 'AS logs for deleted tasks should be removed' );
	}
}
