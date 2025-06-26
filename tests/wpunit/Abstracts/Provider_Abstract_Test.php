<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Abstracts;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\ContainerContract\ContainerInterface;

class Dummy_Provider extends Provider_Abstract {
	public function register(): void {
		// Do nothing
	}
}

class Provider_Abstract_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_not_be_deferred_by_default() {
		/** @var ContainerInterface $container */
		$container = $this->createMock( ContainerInterface::class );
		$provider = new Dummy_Provider( $container );
		$this->assertFalse( $provider->isDeferred() );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_for_provides_by_default() {
		/** @var ContainerInterface $container */
		$container = $this->createMock( ContainerInterface::class );
		$provider = new Dummy_Provider( $container );
		$this->assertEquals( [], $provider->provides() );
	}
}
