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

		$regulator->dispatch( $test_task, $delay );

		$this->assertTrue( $custom_handler_called, 'Custom dispatch handler should have been called' );
		$this->assertSame( $test_task, $handler_received_task, 'Custom handler should receive the task' );
		$this->assertSame( $delay, $handler_received_delay, 'Custom handler should receive the delay' );

		$this->assertNull( $regulator->get_last_scheduled_task_id(), 'Task should not be scheduled when custom handler is used' );
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

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Custom dispatch handler failed' );

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

		$regulator->dispatch( $test_task, $delay );

		$this->assertTrue( $called, 'Custom dispatch handler should have been called' );
		$this->assertNotNull( $regulator->get_last_scheduled_task_id(), 'Task should be scheduled when custom handler is not used' );
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
}
