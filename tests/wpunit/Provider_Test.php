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

	/**
	 * @test
	 */
	public function it_should_assert_that_the_provider_is_not_registered(): void {
		$this->assertTrue( Provider::is_registered() );
	}

	/**
	 * @test
	 */
	public function it_should_evaluate_hook_prefix(): void {
		$this->assertEquals( tests_pigeon_get_hook_prefix(), Provider::get_hook_prefix() );
	}
}
