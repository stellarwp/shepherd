<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Container;

class Regulator_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_have_as_hook_registered(): void {
		$regulator = Provider::get_container()->get( Regulator::class );
		$this->assertSame( 10, has_action( 'pigeon_' . Provider::get_hook_prefix() . '_process_task', [ $regulator, 'process_task' ] ) );
	}
}
