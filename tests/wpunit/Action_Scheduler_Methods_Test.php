<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;

class Action_Scheduler_Methods_Test extends WPTestCase {
	use With_Uopz;
	/**
	 * @before
	 * @after
	 */
	public function unschedule_test_actions(): void {
		as_unschedule_all_actions( 'shepherd_test_hook' );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_a_single_action() {
		$time = time() + 100;
		$hook = 'shepherd_test_hook';
		$args = [ 'test' => 'arg' ];
		$group = 'shepherd_test_group';

		$action_id = Action_Scheduler_Methods::schedule_single_action( $time, $hook, $args, $group, true, 10 );

		$this->assertIsInt( $action_id );
		$this->assertNotFalse( as_next_scheduled_action( $hook, $args, $group ) );
	}

	/**
	 * @test
	 */
	public function it_should_check_if_action_is_scheduled() {
		$time = time() + 200;
		$hook = 'shepherd_test_hook';
		$args = [ 'test' => 'arg2' ];
		$group = 'shepherd_test_group';

		$this->assertFalse( Action_Scheduler_Methods::has_scheduled_action( $hook, $args, $group ) );

		as_schedule_single_action( $time, $hook, $args, $group );

		$this->assertTrue( Action_Scheduler_Methods::has_scheduled_action( $hook, $args, $group ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_zero_when_schedule_single_action_returns_non_integer() {
		$this->set_fn_return( 'as_schedule_single_action', 'not-an-integer' );

		$time = time() + 100;
		$hook = 'shepherd_test_hook_non_int';
		$args = [ 'test' => 'non_int' ];
		$group = 'shepherd_test_group';

		$action_id = Action_Scheduler_Methods::schedule_single_action( $time, $hook, $args, $group );

		$this->assertSame( 0, $action_id );
	}

	/**
	 * @test
	 */
	public function it_should_filter_out_null_actions_from_pending_actions() {
		$finished_action = $this->createMock( \ActionScheduler_FinishedAction::class );
		$null_action = $this->createMock( \ActionScheduler_NullAction::class );
		$normal_action = $this->createMock( \ActionScheduler_Action::class );

		$this->set_class_fn_return(
			Action_Scheduler_Methods::class,
			'get_actions_by_ids',
			[ 1 => $finished_action, 2 => $null_action, 3 => $normal_action ]
		);

		$pending = Action_Scheduler_Methods::get_pending_actions_by_ids( [ 1, 2, 3 ] );

		// Should only contain the normal action (not finished, not null)
		$this->assertCount( 1, $pending );
		$this->assertSame( $normal_action, reset( $pending ) );
	}

	/**
	 * @test
	 */
	public function it_should_get_non_pending_actions_by_ids() {
		$finished_action = $this->createMock( \ActionScheduler_FinishedAction::class );
		$null_action = $this->createMock( \ActionScheduler_NullAction::class );
		$normal_action = $this->createMock( \ActionScheduler_Action::class );
		$canceled_action = $this->createMock( \ActionScheduler_CanceledAction::class );

		$this->set_class_fn_return(
			Action_Scheduler_Methods::class,
			'get_actions_by_ids',
			[ 1 => $finished_action, 2 => $null_action, 3 => $normal_action, 4 => $canceled_action ]
		);

		$non_pending = Action_Scheduler_Methods::get_non_pending_actions_by_ids( [ 1, 2, 3, 4 ] );

		// Should contain finished, null, and canceled actions (canceled extends finished)
		$this->assertCount( 3, $non_pending );
		$this->assertContains( $finished_action, $non_pending );
		$this->assertContains( $null_action, $non_pending );
		$this->assertContains( $canceled_action, $non_pending );
		$this->assertNotContains( $normal_action, $non_pending );
	}

	/**
	 * @test
	 */
	public function it_should_get_pending_and_non_pending_actions_by_ids() {
		$finished_action = $this->createMock( \ActionScheduler_FinishedAction::class );
		$null_action = $this->createMock( \ActionScheduler_NullAction::class );
		$normal_action = $this->createMock( \ActionScheduler_Action::class );
		$canceled_action = $this->createMock( \ActionScheduler_CanceledAction::class );

		$this->set_class_fn_return(
			Action_Scheduler_Methods::class,
			'get_actions_by_ids',
			[ 1 => $finished_action, 2 => $null_action, 3 => $normal_action, 4 => $canceled_action ]
		);

		[ $pending, $non_pending ] = Action_Scheduler_Methods::get_pending_and_non_pending_actions_by_ids( [ 1, 2, 3, 4 ] );

		// Check pending actions (only normal action)
		$this->assertCount( 1, $pending );
		$this->assertContains( $normal_action, $pending );

		// Check non-pending actions (finished, null, and canceled since it extends finished)
		$this->assertCount( 3, $non_pending );
		$this->assertContains( $finished_action, $non_pending );
		$this->assertContains( $null_action, $non_pending );
		$this->assertContains( $canceled_action, $non_pending );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_arrays_when_no_actions_found() {
		$this->set_class_fn_return(
			Action_Scheduler_Methods::class,
			'get_actions_by_ids',
			[]
		);

		$non_pending = Action_Scheduler_Methods::get_non_pending_actions_by_ids( [ 1, 2, 3 ] );
		$this->assertEmpty( $non_pending );

		[ $pending, $non_pending ] = Action_Scheduler_Methods::get_pending_and_non_pending_actions_by_ids( [ 1, 2, 3 ] );
		$this->assertEmpty( $pending );
		$this->assertEmpty( $non_pending );
	}
}
