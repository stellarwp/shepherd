<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Integration;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tests\Tasks\Do_Action_Task;
use StellarWP\Shepherd\Action_Scheduler_Methods;
use StellarWP\Shepherd\Tables\Tasks as Tasks_Table;
use StellarWP\Shepherd\Tasks\Herding;
use StellarWP\Shepherd\Exceptions\ShepherdTaskAlreadyExistsException;
use RuntimeException;
use ActionScheduler;
use function StellarWP\Shepherd\shepherd;
class Task_Model_Abstract_Save_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_delete_stale_tasks_when_saving() {
		// Create a task with a specific args hash
		$task1 = new Do_Action_Task( 'test_arg1', 'test_arg2' );
		shepherd()->dispatch( $task1 );
		$task1_id = $task1->get_id();
		$action1_id = $task1->get_action_id();

		ActionScheduler::store()->mark_complete( $action1_id );

		// Create another task with the same args hash
		$task2 = new Do_Action_Task( 'test_arg1', 'test_arg2' );
		shepherd()->dispatch( $task2 );

		$remaining_tasks = Tasks_Table::get_by_args_hash( $task2->get_args_hash() );

		// Should only have the new task
		$this->assertCount( 1, $remaining_tasks );
		$this->assertNotEquals( $task1_id, $remaining_tasks[0]->get_id() );
		$this->assertEquals( $task2->get_id(), $remaining_tasks[0]->get_id() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_when_multiple_tasks_have_same_action_id() {
		$task1 = new Do_Action_Task( 'unique_arg1' );
		$task1->save();
		$action_id = $task1->get_action_id();

		// Create another task and manually set its action_id to the same value
		$task2 = new Do_Action_Task( 'unique_arg2' );
		$task2->set_action_id( $action_id );
		$task2->save();

		// Create a third task with same action_id
		$task3 = new Do_Action_Task( 'unique_arg3' );
		$task3->set_action_id( $action_id );
		$task3->save();

		// Now try to save a new task with the same args_hash as task2
		$task4 = new Do_Action_Task( 'unique_arg2' );

		$this->expectException( ShepherdTaskAlreadyExistsException::class );
		$this->expectExceptionMessage( 'Multiple tasks found with the same arguments hash.' );

		$task4->save();
	}

	/**
	 * @test
	 */
	public function it_should_handle_mix_of_pending_and_non_pending_actions() {
		// Create multiple tasks with the same args hash
		$task1 = new Do_Action_Task( 'mixed_arg' );
		shepherd()->dispatch( $task1 );

		// Mark as complete (non-pending)
		ActionScheduler::store()->mark_complete( $task1->get_action_id() );

		$task2 = new Do_Action_Task( 'mixed_arg' );
		shepherd()->dispatch( $task2 );

		// Mark as failed (non-pending)
		ActionScheduler::store()->mark_failure( $task2->get_action_id() );

		// Create a new task with same args - should succeed and clean up stale ones
		$task3 = new Do_Action_Task( 'mixed_arg' );
		shepherd()->dispatch( $task3 );

		$remaining_tasks = Tasks_Table::get_by_args_hash( $task3->get_args_hash() );

		// Should only have the newest task
		$this->assertCount( 1, $remaining_tasks );
		$this->assertNotEquals( $task1->get_id(), $remaining_tasks[0]->get_id() );
		$this->assertNotEquals( $task2->get_id(), $remaining_tasks[0]->get_id() );
		$this->assertEquals( $task3->get_id(), $remaining_tasks[0]->get_id() );
	}
}
