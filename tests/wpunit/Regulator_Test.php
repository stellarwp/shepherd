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
		$herding_delay = null;
		
		add_action( 'shepherd_' . Config::get_hook_prefix() . '_task_created', function( $task, $delay ) use ( &$herding_dispatched, &$herding_delay ) {
			if ( $task instanceof Herding ) {
				$herding_dispatched = true;
				$herding_delay = $delay;
			}
		}, 10, 2 );
		
		$regulator->schedule_cleanup_task();
		
		$this->assertTrue( $herding_dispatched );
		$this->assertEquals( 6 * HOUR_IN_SECONDS, $herding_delay );
	}

	/**
	 * @test
	 */
	public function it_should_register_init_hook_for_cleanup_scheduling(): void {
		$regulator = Config::get_container()->get( Regulator::class );
		
		$this->assertSame( 20, has_action( 'init', [ $regulator, 'schedule_cleanup_task' ] ) );
	}
}
