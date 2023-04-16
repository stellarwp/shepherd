<?php

namespace wpunit;

use StellarWP\Pigeon\Config;

class ConfigTest extends \Codeception\TestCase\WPTestCase {

	public function test_get_hook_prefix_throws_exception_if_no_prefix() {
		$this->expectException( \RuntimeException::class );
		Config::get_hook_prefix();
	}

	public function test_get_hook_prefix_returns_set_prefix() {
		Config::set_hook_prefix('prefix');
		$this->assertEquals( 'prefix', Config::get_hook_prefix() );
	}

	public function test_set_hook_prefix_throws_exception_if_prefix_set() {
		$this->expectException( \RuntimeException::class );
		Config::set_hook_prefix( 'tec' );
	}

	public function test_reset_removes_set_prefix() {
		Config::reset();
		$this->expectException( \RuntimeException::class );
		Config::get_hook_prefix();
	}

	/**
	 * @dataProvider invalid_prefix_provider
	 */
	public function test_set_hook_prefix_throws_exception_if_prefix_is_invalid( $prefix ) {
		$this->expectException( \InvalidArgumentException::class );
		Config::set_hook_prefix( $prefix );
	}

	public function invalid_prefix_provider() {
		return [
			[ 'pre fix' ],
			[ 'pre#fix' ],
			[ 'PreFix' ],
		];
	}

}
