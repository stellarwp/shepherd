<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\ContainerContract\ContainerInterface;
use lucatume\DI52\Container as DI52_Container;

class Container_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_compatible_after_updates(): void {
		$container = new Container();

		$this->assertInstanceOf( Container::class, $container );
		$this->assertInstanceOf( ContainerInterface::class, $container );
		$this->assertInstanceOf( DI52_Container::class, $container );
	}
}
