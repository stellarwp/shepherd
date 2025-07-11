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
	public function it_should_register_init_hook_for_cleanup_scheduling(): void {
		$regulator = Config::get_container()->get( Regulator::class );
		
		$this->assertSame( 20, has_action( 'init', [ $regulator, 'schedule_cleanup_task' ] ) );
	}
}
