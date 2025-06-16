<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Container;

class Provider_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_get_the_container(): void {
		$container = tests_pigeon_get_container();

		$this->assertInstanceOf( Container::class, $container );
		$this->assertInstanceOf( Container::class, Provider::get_container() );
		$this->assertSame( $container, Provider::get_container() );
	}
}
