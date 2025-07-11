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
		
		// Track dispatch calls
		$dispatched_tasks = [];
		$dispatched_delays = [];
		
		// Mock the dispatch method to capture calls
		$this->set_fn_return( [ $regulator, 'dispatch' ], function( $task, $delay = 0 ) use ( &$dispatched_tasks, &$dispatched_delays ) {
			$dispatched_tasks[] = $task;
			$dispatched_delays[] = $delay;
			return true;
		} );
		
		// Call the method
		$regulator->schedule_cleanup_task();
		
		// Verify a Herding task was dispatched
		$this->assertCount( 1, $dispatched_tasks );
		$this->assertInstanceOf( Herding::class, $dispatched_tasks[0] );
		
		// Verify it was scheduled with 6 hour delay
		$this->assertEquals( 6 * HOUR_IN_SECONDS, $dispatched_delays[0] );
	}

	/**
	 * @test
	 */
	public function it_should_register_init_hook_for_cleanup_scheduling(): void {
		$regulator = Config::get_container()->get( Regulator::class );
		
		// Check that the init hook is registered
		$this->assertSame( 20, has_action( 'init', [ $regulator, 'schedule_cleanup_task' ] ) );
	}
}
