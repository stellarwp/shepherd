<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Dummy_Retryable {
	use Retryable;

	private int $current_try = 1;

	public static function set_max_retries( int $retries ) {
		static::$max_retries = $retries;
	}

	public function get_current_try(): int {
		return $this->current_try;
	}

	public function set_current_try( int $try ) {
		$this->current_try = $try;
	}
}

class Retryable_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_not_be_retryable_by_default() {
		$dummy = new Dummy_Retryable();
		$this->assertFalse( $dummy->is_retryable() );
	}

	/**
	 * @test
	 */
	public function it_should_be_retryable_if_max_retries_is_not_one() {
		$dummy = new Dummy_Retryable();
		Dummy_Retryable::set_max_retries( 5 );
		$this->assertTrue( $dummy->is_retryable() );

		Dummy_Retryable::set_max_retries( 0 );
		$this->assertTrue( $dummy->is_retryable() );
	}

	/**
	 * @test
	 */
	public function it_should_retry_if_current_try_is_less_than_max() {
		$dummy = new Dummy_Retryable();
		Dummy_Retryable::set_max_retries( 3 );
		$dummy->set_current_try( 1 );
		$this->assertTrue( $dummy->should_retry() );
		$dummy->set_current_try( 2 );
		$this->assertTrue( $dummy->should_retry() );
		$dummy->set_current_try( 3 );
		$this->assertFalse( $dummy->should_retry() );
	}

	/**
	 * @test
	 */
	public function it_should_always_retry_if_max_is_zero_or_less() {
		$dummy = new Dummy_Retryable();
		Dummy_Retryable::set_max_retries( 0 );
		$dummy->set_current_try( 1000 );
		$this->assertTrue( $dummy->should_retry() );

		Dummy_Retryable::set_max_retries( -1 );
		$this->assertTrue( $dummy->should_retry() );
	}

	/**
	 * @test
	 */
	public function it_should_have_thirty_retry_delay_by_default() {
		$dummy = new Dummy_Retryable();
		$this->assertEquals( 30, $dummy->get_retry_delay() );
	}
}
