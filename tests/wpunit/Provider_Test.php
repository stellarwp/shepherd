<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Provider_Test extends WPTestCase {
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
		$this->assertEquals( tests_shepherd_get_hook_prefix(), Config::get_hook_prefix() );
	}
}
