<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tables\Utility;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Tables;
use StellarWP\Pigeon\Tables\Utility\Safe_Dynamic_Prefix;

class Safe_Dynamic_Prefix_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_calculate_max_hook_prefix_length(): void {
		$this->assertEquals( 5, Config::get_container()->get( Safe_Dynamic_Prefix::class )->get_max_length() );
	}

	/**
	 * @test
	 */
	public function it_should_return_safe_hook_prefix_for_short_prefix(): void {
		Config::set_hook_prefix( 'short' );

		// Short prefix should be returned as-is
		$this->assertEquals( 'short', Config::get_container()->get( Safe_Dynamic_Prefix::class )->get() );
	}

	/**
	 * @test
	 */
	public function it_should_trim_long_hook_prefix_to_safe_length(): void {
		$very_long_prefix = str_repeat( 'a', 100 ); // 100 characters
		Config::set_hook_prefix( $very_long_prefix );

		// Safe prefix should be trimmed to max length
		$this->assertEquals( 'aaaaa', Config::get_container()->get( Safe_Dynamic_Prefix::class )->get() );
	}

	/**
	 * @after
	 */
	public function reset(): void {
		Config::set_hook_prefix( tests_pigeon_get_hook_prefix() );
	}
}
