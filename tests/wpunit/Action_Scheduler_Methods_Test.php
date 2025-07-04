<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Action_Scheduler_Methods_Test extends WPTestCase {
	/**
	 * @before
	 * @after
	 */
	public function unschedule_test_actions(): void {
		as_unschedule_all_actions( 'pigeon_test_hook' );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_a_single_action() {
		$time = time() + 100;
		$hook = 'pigeon_test_hook';
		$args = [ 'test' => 'arg' ];
		$group = 'pigeon_test_group';

		$action_id = Action_Scheduler_Methods::schedule_single_action( $time, $hook, $args, $group, true, 10 );

		$this->assertIsInt( $action_id );
		$this->assertNotFalse( as_next_scheduled_action( $hook, $args, $group ) );
	}

	/**
	 * @test
	 */
	public function it_should_check_if_action_is_scheduled() {
		$time = time() + 200;
		$hook = 'pigeon_test_hook';
		$args = [ 'test' => 'arg2' ];
		$group = 'pigeon_test_group';

		$this->assertFalse( Action_Scheduler_Methods::has_scheduled_action( $hook, $args, $group ) );

		as_schedule_single_action( $time, $hook, $args, $group );

		$this->assertTrue( Action_Scheduler_Methods::has_scheduled_action( $hook, $args, $group ) );
	}
}
