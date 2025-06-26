<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Dummy_Debouncable {
	use Debouncable;
}

class Debouncable_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_be_debouncable_by_default() {
		$dummy = new Dummy_Debouncable();
		$this->assertTrue( $dummy->is_debouncable() );
	}

	/**
	 * @test
	 */
	public function it_should_have_zero_delay_by_default() {
		$dummy = new Dummy_Debouncable();
		$this->assertEquals( 0, $dummy->get_debounce_delay() );
	}

	/**
	 * @test
	 */
	public function it_should_have_zero_delay_on_failure_by_default() {
		$dummy = new Dummy_Debouncable();
		$this->assertEquals( 0, $dummy->get_debounce_delay_on_failure() );
	}
}
