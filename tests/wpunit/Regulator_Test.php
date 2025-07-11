<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Regulator_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_have_as_hook_registered(): void {
		$regulator = Config::get_container()->get( Regulator::class );
		$this->assertSame( 10, has_action( 'shepherd_' . Config::get_hook_prefix() . '_process_task', [ $regulator, 'process_task' ] ) );
	}
}
