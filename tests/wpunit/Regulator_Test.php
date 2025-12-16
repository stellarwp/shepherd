<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tasks\Herding;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\Shepherd\Tests\Tasks\Do_Action_Task;
use StellarWP\Shepherd\Tests\Tasks\Do_Prefixed_Action_Task;
use StellarWP\Shepherd\Tests\Traits\With_AS_Assertions;
use Exception;

class Regulator_Test extends WPTestCase {
	use With_Uopz;
	use With_AS_Assertions;

	/**
	 * @test
	 */
	public function it_should_have_as_hook_registered(): void {
		$regulator = Config::get_container()->get( Regulator::class );
		$this->assertSame( 10, has_action( 'shepherd_' . Config::get_hook_prefix() . '_process_task', [ $regulator, 'process_task' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_cleanup_task_on_init(): void {
		$regulator = Config::get_container()->get( Regulator::class );

		$herding_dispatched = false;

		add_action( 'shepherd_' . Config::get_hook_prefix() . '_task_created', function( $task ) use ( &$herding_dispatched ) {
			if ( $task instanceof Herding ) {
				$herding_dispatched = true;
			}
		} );

		$regulator->schedule_cleanup_task();

		$this->assertTrue( $herding_dispatched );

		// Also verify the task was scheduled with the correct delay by checking Action Scheduler
		$last_task_id = shepherd()->get_last_scheduled_task_id();
		$this->assertNotNull( $last_task_id );
	}

	/**
	 * @test
	 */
	public function it_should_register_wp_loaded_hook_for_cleanup_scheduling(): void {
		$regulator = Config::get_container()->get( Regulator::class );

		$this->assertSame( 20, has_action( 'wp_loaded', [ $regulator, 'schedule_cleanup_task' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_dispatch_immediately_when_action_scheduler_initialized(): void {
		$regulator = Config::get_container()->get( Regulator::class );

		$task = new Do_Action_Task();

		$this->assertSame( 0, did_action( $task->get_task_name() ) );

		$regulator->dispatch( $task );

		$this->assertSame( 0, did_action( $task->get_task_name() ) );

		$last_scheduled_task_id = $regulator->get_last_scheduled_task_id();
		$this->assertNotNull( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertSame( 1, did_action( $task->get_task_name() ) );
	}

	/**
	 * @test
	 */
	public function it_should_process_task_synchronously_when_tables_not_registered(): void {
		$prefix = Config::get_hook_prefix();

		$this->set_fn_return( 'did_action', function( $action ) use ( $prefix ) {
			if ( $action === "shepherd_{$prefix}_tables_registered" ) {
				return 0;
			}

			return did_action( $action );
		}, true );

		$regulator = Config::get_container()->get( Regulator::class );

		$test_task = new Do_Action_Task();

		$this->assertSame( 0, did_action( $test_task->get_task_name() ) );
		$this->assertSame( 0, did_action( "shepherd_{$prefix}_dispatched_sync" ) );

		$sync_dispatch_fired = false;
		$dispatched_task = null;
		add_action( "shepherd_{$prefix}_dispatched_sync", function( $task ) use ( &$sync_dispatch_fired, &$dispatched_task ) {
			$sync_dispatch_fired = true;
			$dispatched_task = $task;
		} );

		$regulator->dispatch( $test_task );

		$this->assertSame( 1, did_action( $test_task->get_task_name() ) );
		$this->assertSame( 1, did_action( "shepherd_{$prefix}_dispatched_sync" ) );
		$this->assertTrue( $sync_dispatch_fired, 'Synchronous dispatch action should be fired' );
		$this->assertSame( $test_task, $dispatched_task, 'The dispatched task should be the same instance' );
	}

	/**
	 * @test
	 */
	public function it_should_respect_filter_to_disable_sync_dispatch_when_tables_not_registered(): void {
		$prefix = Config::get_hook_prefix();

		$this->set_fn_return( 'did_action', function( $action ) use ( $prefix ) {
			if ( $action === "shepherd_{$prefix}_tables_registered" ) {
				return 0;
			}

			return did_action( $action );
		}, true );

		$regulator = Config::get_container()->get( Regulator::class );

		add_filter( "shepherd_{$prefix}_should_dispatch_sync_on_tables_unavailable", '__return_false' );

		$test_task = new Do_Action_Task();

		$this->assertSame( 0, did_action( $test_task->get_task_name() ) );
		$this->assertSame( 0, did_action( "shepherd_{$prefix}_dispatched_sync" ) );

		$sync_dispatch_fired = false;
		add_action( "shepherd_{$prefix}_dispatched_sync", function() use ( &$sync_dispatch_fired ) {
			$sync_dispatch_fired = true;
		} );

		$regulator->dispatch( $test_task );

		$this->assertSame( 0, did_action( $test_task->get_task_name() ) );
		$this->assertSame( 0, did_action( "shepherd_{$prefix}_dispatched_sync" ) );
		$this->assertFalse( $sync_dispatch_fired, 'Synchronous dispatch should not occur when disabled by filter' );
	}

	/**
	 * @test
	 */
	public function it_should_default_sync_dispatch_filter_based_on_delay(): void {
		$prefix = Config::get_hook_prefix();

		$this->set_fn_return( 'did_action', function( $action ) use ( $prefix ) {
			if ( $action === "shepherd_{$prefix}_tables_registered" ) {
				return 0;
			}

			return did_action( $action );
		}, true );

		$regulator = Config::get_container()->get( Regulator::class );

		$test_task1 = new Do_Prefixed_Action_Task( 'foo' );

		$this->assertEquals( 0, did_action( $test_task1->get_task_name() ) );
		$regulator->dispatch( $test_task1 );
		$this->assertEquals( 1, did_action( $test_task1->get_task_name() ) );

		$test_task2 = new Do_Prefixed_Action_Task( 'bar' );

		$this->assertEquals( 0, did_action( $test_task2->get_task_name() ) );
		$regulator->dispatch( $test_task2, 300 );
		$this->assertEquals( 0, did_action( $test_task2->get_task_name() ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_schedule_cleanup_task_when_tables_not_registered(): void {
		$prefix = Config::get_hook_prefix();

		// Mock did_action to make it appear tables are not registered
		$this->set_fn_return( 'did_action', function( $action ) use ( $prefix ) {
			if ( $action === "shepherd_{$prefix}_tables_registered" ) {
				return 0;
			}

			return did_action( $action );
		}, true );

		$current_count = did_action( 'shepherd_' . $prefix . '_cleanup_task_scheduled' );

		$regulator = Config::get_container()->get( Regulator::class );

		$regulator->schedule_cleanup_task();

		$this->assertEquals( $current_count, did_action( 'shepherd_' . $prefix . '_cleanup_task_scheduled' ), 'Cleanup task should not be scheduled when tables are not registered' );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_cleanup_task_when_tables_are_registered(): void {
		$prefix = Config::get_hook_prefix();

		$current_count = did_action( 'shepherd_' . $prefix . '_cleanup_task_scheduled' );

		$regulator = Config::get_container()->get( Regulator::class );

		$regulator->schedule_cleanup_task();

		$this->assertEquals( $current_count + 1, did_action( 'shepherd_' . $prefix . '_cleanup_task_scheduled' ), 'Cleanup task should not be scheduled when tables are not registered' );
	}

	/**
	 * @test
	 */
	public function it_should_use_custom_dispatch_handler_when_provided_via_filter(): void {
		$prefix = Config::get_hook_prefix();
		$regulator = Config::get_container()->get( Regulator::class );

		$custom_handler_called = false;
		$handler_received_task = null;
		$handler_received_delay = null;

		add_filter( "shepherd_{$prefix}_dispatch_handler", function( $handler, $task, $delay ) use ( &$custom_handler_called, &$handler_received_task, &$handler_received_delay ) {
			return function( $task, $delay ) use ( &$custom_handler_called, &$handler_received_task, &$handler_received_delay ) {
				$custom_handler_called = true;
				$handler_received_task = $task;
				$handler_received_delay = $delay;
			};
		}, 10, 3 );

		$test_task = new Do_Action_Task();
		$delay = 60;

		$last_task_id = $regulator->get_last_scheduled_task_id();

		$regulator->dispatch( $test_task, $delay );

		$this->assertTrue( $custom_handler_called, 'Custom dispatch handler should have been called' );
		$this->assertSame( $test_task, $handler_received_task, 'Custom handler should receive the task' );
		$this->assertSame( $delay, $handler_received_delay, 'Custom handler should receive the delay' );

		$this->assertSame( $last_task_id, $regulator->get_last_scheduled_task_id(), 'Task should not be scheduled when custom handler is used' );
	}

	/**
	 * @test
	 */
	public function it_should_fire_fail_action_when_throwing_exception_in_custom_dispatch_handler(): void {
		$prefix = Config::get_hook_prefix();
		$regulator = Config::get_container()->get( Regulator::class );

		add_filter( "shepherd_{$prefix}_dispatch_handler", function( $handler, $task, $delay ) {
			return function( $task, $delay ) {
				throw new Exception( 'Custom dispatch handler failed' );
			};
		}, 10, 3 );

		$test_task = new Do_Action_Task();
		$delay = 60;

		$this->assertSame( 0, did_action( "shepherd_{$prefix}_task_scheduling_failed" ) );
		$regulator->dispatch( $test_task, $delay );
		$this->assertTrue( 0 < did_action( "shepherd_{$prefix}_task_scheduling_failed" ) );
	}

	/**
	 * @test
	 */
	public function it_should_do_default_when_returning_null(): void {
		$prefix = Config::get_hook_prefix();
		$regulator = Config::get_container()->get( Regulator::class );

		$called = false;

		// Add a custom dispatch handler via filter
		add_filter( "shepherd_{$prefix}_dispatch_handler", function( $handler, $task, $delay ) use ( &$called ) {
			return function( $task, $delay ) use ( &$called ) {
				$called = true;
			};
		}, 10, 3 );

		// Add a custom dispatch handler via filter
		add_filter( "shepherd_{$prefix}_dispatch_handler", function( $handler, $task, $delay ) {
			return null;
		}, 10, 3 );

		$test_task = new Do_Action_Task();
		$delay = 60;

		$last_task_id = $regulator->get_last_scheduled_task_id();

		$regulator->dispatch( $test_task, $delay );

		$this->assertFalse( $called, 'Custom dispatch handler should have been called' );
		$this->assertNotSame( $last_task_id, $regulator->get_last_scheduled_task_id(), 'Task should be scheduled when custom handler is not used' );
	}

	/**
	 * @test
	 */
	public function it_should_do_default_when_returning_non_callable(): void {
		$prefix = Config::get_hook_prefix();
		$regulator = Config::get_container()->get( Regulator::class );


		// Add a custom dispatch handler via filter
		add_filter( "shepherd_{$prefix}_dispatch_handler", function( $handler, $task, $delay ) {
			return 'not a callable';
		}, 10, 3 );

		$test_task = new Do_Action_Task();
		$delay = 60;

		$regulator->dispatch( $test_task, $delay );

		$this->assertNotNull( $regulator->get_last_scheduled_task_id(), 'Task should be scheduled when custom handler is not callable' );
	}

	/**
	 * @test
	 */
	public function it_should_run_tasks_synchronously_when_tables_not_registered(): void {
		$prefix = Config::get_hook_prefix();

		$this->set_fn_return( 'did_action', function( $action ) use ( $prefix ) {
			if ( $action === "shepherd_{$prefix}_tables_registered" ) {
				return 0;
			}

			return did_action( $action );
		}, true );

		$regulator = Config::get_container()->get( Regulator::class );

		$task1 = new Do_Prefixed_Action_Task( 'run_task_1' );
		$task2 = new Do_Prefixed_Action_Task( 'run_task_2' );

		$this->assertSame( 0, did_action( $task1->get_task_name() ) );
		$this->assertSame( 0, did_action( $task2->get_task_name() ) );

		$sync_run_count = 0;
		add_action( "shepherd_{$prefix}_task_run_sync", function() use ( &$sync_run_count ) {
			$sync_run_count++;
		} );

		$regulator->run( [ $task1, $task2 ] );

		$this->assertSame( 1, did_action( $task1->get_task_name() ), 'First task should have run' );
		$this->assertSame( 1, did_action( $task2->get_task_name() ), 'Second task should have run' );
		$this->assertSame( 2, $sync_run_count, 'sync action should have fired twice' );
	}

	/**
	 * @test
	 */
	public function it_should_run_tasks_and_dispatch_when_tables_registered(): void {
		$prefix = Config::get_hook_prefix();
		$regulator = Config::get_container()->get( Regulator::class );

		$task1 = new Do_Prefixed_Action_Task( 'run_dispatch_1' );
		$task2 = new Do_Prefixed_Action_Task( 'run_dispatch_2' );

		$this->assertSame( 0, did_action( $task1->get_task_name() ) );
		$this->assertSame( 0, did_action( $task2->get_task_name() ) );

		$before_run_count = 0;
		$after_run_count = 0;
		$tasks_finished_count = 0;

		add_action( "shepherd_{$prefix}_task_before_run", function() use ( &$before_run_count ) {
			$before_run_count++;
		} );

		add_action( "shepherd_{$prefix}_task_after_run", function() use ( &$after_run_count ) {
			$after_run_count++;
		} );

		add_action( "shepherd_{$prefix}_tasks_finished", function() use ( &$tasks_finished_count ) {
			$tasks_finished_count++;
		} );

		$regulator->run( [ $task1, $task2 ] );

		$this->assertSame( 1, did_action( $task1->get_task_name() ), 'First task should have run' );
		$this->assertSame( 1, did_action( $task2->get_task_name() ), 'Second task should have run' );
		$this->assertSame( 2, $before_run_count, 'before_run action should have fired twice' );
		$this->assertSame( 2, $after_run_count, 'after_run action should have fired twice' );
		$this->assertSame( 1, $tasks_finished_count, 'tasks_finished action should have fired once' );
	}

	/**
	 * @test
	 */
	public function it_should_execute_before_callable_for_each_task(): void {
		$regulator = Config::get_container()->get( Regulator::class );

		$task1 = new Do_Prefixed_Action_Task( 'before_callable_1' );
		$task2 = new Do_Prefixed_Action_Task( 'before_callable_2' );

		$before_tasks = [];

		$regulator->run( [ $task1, $task2 ], [
			'before' => function( $task ) use ( &$before_tasks ) {
				$before_tasks[] = $task;
			},
		] );

		$this->assertCount( 2, $before_tasks );
		$this->assertSame( $task1->get_args_hash(), $before_tasks[0]->get_args_hash() );
		$this->assertSame( $task2->get_args_hash(), $before_tasks[1]->get_args_hash() );
	}

	/**
	 * @test
	 */
	public function it_should_execute_after_callable_for_each_task(): void {
		$regulator = Config::get_container()->get( Regulator::class );

		$task1 = new Do_Prefixed_Action_Task( 'after_callable_1' );
		$task2 = new Do_Prefixed_Action_Task( 'after_callable_2' );

		$after_tasks = [];

		$regulator->run( [ $task1, $task2 ], [
			'after' => function( $task ) use ( &$after_tasks ) {
				$after_tasks[] = $task;
			},
		] );

		$this->assertCount( 2, $after_tasks );
		$this->assertSame( $task1->get_args_hash(), $after_tasks[0]->get_args_hash() );
		$this->assertSame( $task2->get_args_hash(), $after_tasks[1]->get_args_hash() );
	}

	/**
	 * @test
	 */
	public function it_should_execute_always_callable_after_all_tasks(): void {
		$regulator = Config::get_container()->get( Regulator::class );

		$task1 = new Do_Prefixed_Action_Task( 'always_callable_1' );
		$task2 = new Do_Prefixed_Action_Task( 'always_callable_2' );

		$always_executed = false;
		$always_tasks = null;

		$regulator->run( [ $task1, $task2 ], [
			'always' => function( $tasks ) use ( &$always_executed, &$always_tasks ) {
				$always_executed = true;
				$always_tasks = $tasks;
			},
		] );

		$this->assertTrue( $always_executed, 'always callable should have been executed' );
		$this->assertCount( 2, $always_tasks );
	}

	/**
	 * @test
	 */
	public function it_should_not_dispatch_already_scheduled_tasks_when_running(): void {
		$prefix = Config::get_hook_prefix();
		$regulator = Config::get_container()->get( Regulator::class );

		$task = new Do_Prefixed_Action_Task( 'already_scheduled_run' );

		// First dispatch the task
		$regulator->dispatch( $task );
		$first_task_id = $regulator->get_last_scheduled_task_id();

		$created_count = did_action( "shepherd_{$prefix}_task_created" );

		// Now run it - should not dispatch again since it's already scheduled
		$regulator->run( [ $task ] );

		// Check that task_created was NOT fired again
		$this->assertSame( $created_count, did_action( "shepherd_{$prefix}_task_created" ), 'Task should not be dispatched again if already scheduled' );
	}
}
