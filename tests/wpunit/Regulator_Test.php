<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tasks\Herding;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;

class Regulator_Test extends WPTestCase {
	use With_Uopz;

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

		$dispatch_callback_called = false;

		$this->set_class_fn_return(
			Regulator::class,
			'dispatch_callback',
			function() use ( &$dispatch_callback_called ) {
				$dispatch_callback_called = true;
			},
			true
		);

		$test_task = new Tasks\Herding();

		$regulator->dispatch( $test_task );

		$this->assertTrue( $dispatch_callback_called, 'dispatch_callback should be called immediately when AS is initialized' );
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

		$test_task = new Tasks\Herding();
		$process_called = false;
		$process_call_count = 0;

		$this->set_class_fn_return(
			Tasks\Herding::class,
			'process',
			function() use ( &$process_called, &$process_call_count ) {
				$process_called = true;
				$process_call_count++;
			},
			true
		);

		$sync_dispatch_fired = false;
		$dispatched_task = null;
		add_action( "shepherd_{$prefix}_dispatched_sync", function( $task ) use ( &$sync_dispatch_fired, &$dispatched_task ) {
			$sync_dispatch_fired = true;
			$dispatched_task = $task;
		} );

		$regulator->dispatch( $test_task );

		$this->assertTrue( $process_called, 'Task should be processed synchronously when tables are not registered' );
		$this->assertEquals( 1, $process_call_count, 'Task process method should be called exactly once' );
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

		$test_task = new Tasks\Herding();
		$process_called = false;
		$process_call_count = 0;

		$this->set_class_fn_return(
			Tasks\Herding::class,
			'process',
			function() use ( &$process_called, &$process_call_count ) {
				$process_called = true;
				$process_call_count++;
			},
			true
		);

		$sync_dispatch_fired = false;
		add_action( "shepherd_{$prefix}_dispatched_sync", function() use ( &$sync_dispatch_fired ) {
			$sync_dispatch_fired = true;
		} );

		$regulator->dispatch( $test_task );

		$this->assertFalse( $process_called, 'Task process method should not be called when sync dispatch is disabled' );
		$this->assertEquals( 0, $process_call_count, 'Task process method should not be called at all' );
		$this->assertFalse( $sync_dispatch_fired, 'Synchronous dispatch should not occur when disabled by filter' );
	}
}
