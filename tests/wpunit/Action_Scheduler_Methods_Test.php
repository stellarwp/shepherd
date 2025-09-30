<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use ActionScheduler_NullAction;
use ActionScheduler_FinishedAction;
use ActionScheduler_CanceledAction;
use ActionScheduler_Action;
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
		$finished_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'finished' ], 'shepherd_test_group' );
		\ActionScheduler::store()->mark_complete( $finished_id );

		$pending_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'pending' ], 'shepherd_test_group' );

		$pending = Action_Scheduler_Methods::get_pending_actions_by_ids( [ $finished_id, $pending_id ] );

		$this->assertCount( 1, $pending );
		$this->assertArrayHasKey( $pending_id, $pending );
		$this->assertInstanceOf( \ActionScheduler_Action::class, $pending[ $pending_id ] );
	}

	/**
	 * @test
	 */
	public function it_should_get_non_pending_actions_by_ids() {
		$finished_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'finished' ], 'shepherd_test_group' );
		\ActionScheduler::store()->mark_complete( $finished_id );

		$canceled_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'canceled' ], 'shepherd_test_group' );
		\ActionScheduler::store()->cancel_action( $canceled_id );

		$pending_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'pending' ], 'shepherd_test_group' );

		$non_pending = Action_Scheduler_Methods::get_non_pending_actions_by_ids( [ $finished_id, $canceled_id, $pending_id ] );

		$this->assertCount( 2, $non_pending );
		$this->assertArrayHasKey( $finished_id, $non_pending );
		$this->assertArrayHasKey( $canceled_id, $non_pending );
		$this->assertArrayNotHasKey( $pending_id, $non_pending );
		$this->assertInstanceOf( ActionScheduler_FinishedAction::class, $non_pending[ $finished_id ] );
		$this->assertInstanceOf( ActionScheduler_CanceledAction::class, $non_pending[ $canceled_id ] );
	}

	/**
	 * @test
	 */
	public function it_should_get_pending_and_non_pending_actions_by_ids() {
		$finished_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'finished' ], 'shepherd_test_group' );
		\ActionScheduler::store()->mark_complete( $finished_id );

		$canceled_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'canceled' ], 'shepherd_test_group' );
		\ActionScheduler::store()->cancel_action( $canceled_id );

		$pending_id = as_schedule_single_action( time() + 100, 'shepherd_test_hook', [ 'type' => 'pending' ], 'shepherd_test_group' );

		[ $pending, $non_pending ] = Action_Scheduler_Methods::get_pending_and_non_pending_actions_by_ids( [ $finished_id, $canceled_id, $pending_id ] );

		$this->assertCount( 1, $pending );
		$this->assertArrayHasKey( $pending_id, $pending );
		$this->assertInstanceOf( ActionScheduler_Action::class, $pending[ $pending_id ] );

		$this->assertCount( 2, $non_pending );
		$this->assertArrayHasKey( $finished_id, $non_pending );
		$this->assertArrayHasKey( $canceled_id, $non_pending );
		$this->assertInstanceOf( ActionScheduler_FinishedAction::class, $non_pending[ $finished_id ] );
		$this->assertInstanceOf( ActionScheduler_CanceledAction::class, $non_pending[ $canceled_id ] );
	}

	/**
	 * @test
	 */
	public function it_should_handle_empty_action_ids() {
		$pending = Action_Scheduler_Methods::get_pending_actions_by_ids( [ 1, 2, 3 ] );
		$this->assertEmpty( $pending );

		$non_pending = Action_Scheduler_Methods::get_non_pending_actions_by_ids( [ 1, 2, 3 ] );
		$this->assertNotEmpty( $non_pending );

		foreach ( $non_pending as $action ) {
			$this->assertInstanceOf( ActionScheduler_NullAction::class, $action );
		}

		$this->assertCount( 3, $non_pending );

		[ $pending, $non_pending ] = Action_Scheduler_Methods::get_pending_and_non_pending_actions_by_ids( [ 1, 2, 3 ] );
		$this->assertEmpty( $pending );
		$this->assertCount( 3, $non_pending );

		foreach ( $non_pending as $action ) {
			$this->assertInstanceOf( ActionScheduler_NullAction::class, $action );
		}
	}
}
