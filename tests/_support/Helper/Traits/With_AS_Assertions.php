<?php

namespace StellarWP\Pigeon\Tests\Traits;

use StellarWP\Pigeon\Contracts\Task;
use StellarWP\Pigeon\Abstracts\Task_Model_Abstract;
use StellarWP\Pigeon\Action_Scheduler_Methods;
use ActionScheduler_Action;
use ActionScheduler_QueueRunner as Runner;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Provider;
use StellarWP\DB\DB;
use function StellarWP\Pigeon\pigeon;

trait With_AS_Assertions {
	/**
	 * @before
	 * @after
	 */
	protected function delete_actions_between_runs(): void {
		Config::get_container()->get( Task_Model_Abstract::TABLE_INTERFACE )->empty_table();
		DB::query( DB::prepare( "DELETE FROM %i", DB::prefix( 'actionscheduler_actions' ) ) );
	}

	protected function get_task_from_id( int $task_id ): Task {
		return ( Task_Model_Abstract::TABLE_INTERFACE )::get_by_id( $task_id );
	}

	protected function get_task_from_action_id( int $action_id ): Task {
		return ( Task_Model_Abstract::TABLE_INTERFACE )::get_by_action_id( $action_id );
	}

	protected function get_task_from_args_hash( string $args_hash ): Task {
		return ( Task_Model_Abstract::TABLE_INTERFACE )::get_by_args_hash( $args_hash );
	}

	protected function get_action_from_task( Task $task ): ActionScheduler_Action {
		return Action_Scheduler_Methods::get_action_by_id( $task->get_action_id() );
	}

	protected function assertTaskHasActionPending( int $task_id ): void {
		$task = $this->get_task_from_id( $task_id );
		$action = $this->get_action_from_task( $task );

		$this->assertEquals( pigeon()->get_hook(), $action->get_hook() );

		$this->assertFalse( $action->is_finished() );
	}

	protected function assertTaskHasActionFinished( int $task_id ): void {
		$task = $this->get_task_from_id( $task_id );
		$action = $this->get_action_from_task( $task );

		$this->assertEquals( pigeon()->get_hook(), $action->get_hook() );

		$this->assertTrue( $action->is_finished() );
	}

	protected function assertTaskIsScheduledForExecutionAt( int $task_id, int $timestamp ): void {
		$task = $this->get_task_from_id( $task_id );
		$action = $this->get_action_from_task( $task );

		$this->assertEquals( $timestamp, $action->get_schedule()->get_date()->getTimestamp() );
	}

	protected function assertTaskExecutesWithoutErrors( int $task_id ): void {
		$action_id = $this->get_task_from_id( $task_id )->get_action_id();
		Runner::instance()->run();

		$status = DB::get_var(
			DB::prepare(
				"SELECT status FROM %i WHERE action_id = %d",
				DB::prefix( 'actionscheduler_actions' ),
				$action_id
			)
		);

		$this->assertSame( 'complete', $status );

		$this->assertTaskHasActionFinished( $task_id );
	}

	protected function assertTaskExecutesFailsAndReschedules( int $task_id ): void {
		$action_id = $this->get_task_from_id( $task_id )->get_action_id();
		Runner::instance()->run();

		$status = DB::get_var(
			DB::prepare(
				"SELECT status FROM %i WHERE action_id = %d",
				DB::prefix( 'actionscheduler_actions' ),
				$action_id
			)
		);

		$this->assertSame( 'failed', $status );

		$this->assertTaskHasActionPending( $task_id );
	}

	protected function assertTaskExecutesFails( int $task_id ): void {
		$action_id = $this->get_task_from_id( $task_id )->get_action_id();
		Runner::instance()->run();

		$status = DB::get_var(
			DB::prepare(
				"SELECT status FROM %i WHERE action_id = %d",
				DB::prefix( 'actionscheduler_actions' ),
				$action_id
			)
		);

		$this->assertSame( 'failed', $status );

		$this->assertTaskHasActionFinished( $task_id );
	}
}
