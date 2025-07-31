<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Contracts\Logger;
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

		$task = new Do_Action_Task();
		$shepherd->dispatch( $task );
		$task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $task_id );

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

		$this->delete_tasks_action( $task );

		$task_exists_after = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id
			)
		);
		$this->assertEquals( 0, $task_exists_after );

		$logs_exist_after = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id );
		$this->assertCount( 0, $logs_exist_after );
	}

	/**
	 * @test
	 */
	public function it_should_delete_multiple_tasks_for_same_action(): void {
		$shepherd = shepherd();

		$task1 = new Do_Action_Task( 'arg1' );
		$shepherd->dispatch( $task1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();

		$task2 = new Do_Action_Task( 'arg2' );
		$shepherd->dispatch( $task2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $task_id_1 );

		// Manually set both tasks to have the same action_id for testing.
		$common_action_id = $task1->get_action_id();
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

		$this->delete_tasks_action( $task1 );

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

		$logs_count_after = Config::get_container()->get( Logger::class )->retrieve_logs( $task_id_1 );
		$this->assertCount( 0, $logs_count_after );
	}

	/**
	 * @test
	 */
	public function it_should_handle_non_existent_action_id_gracefully(): void {
		$provider = $this->get_provider();

		$non_existent_action_id = PHP_INT_MAX;

		$action_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE action_id = %d',
				DB::prefix( 'actionscheduler_actions' ),
				$non_existent_action_id
			)
		);
		$this->assertEquals( 0, $action_exists );

		$provider->delete_tasks_on_action_deletion( $non_existent_action_id );

		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function it_should_only_delete_tasks_for_specified_action(): void {
		$shepherd = shepherd();

		$task1 = new Do_Action_Task( 'arg1' );
		$shepherd->dispatch( $task1 );
		$task_id_1 = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $task_id_1 );

		$task2 = new Do_Action_Task( 'arg2' );
		$shepherd->dispatch( $task2 );
		$task_id_2 = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $task_id_2 );

		$this->delete_tasks_action( $task1 );

		$task1_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id_1
			)
		);
		$this->assertEquals( 0, $task1_exists );

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
		$shepherd = shepherd();
		$task = new Do_Action_Task();
		$shepherd->dispatch( $task );
		$task_id = $shepherd->get_last_scheduled_task_id();

		$this->delete_tasks_action( $task );

		$task_exists = DB::get_var(
			DB::prepare(
				'SELECT COUNT(*) FROM %i WHERE %i = %d',
				Tasks::table_name(),
				Tasks::uid_column(),
				$task_id
			)
		);
		$this->assertEquals( 0, $task_exists );
	}
}
