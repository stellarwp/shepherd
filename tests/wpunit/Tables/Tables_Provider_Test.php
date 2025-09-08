<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tables;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use StellarWP\Schema\Register;

class Tables_Provider_Test extends WPTestCase {
	use With_Uopz;
	/**
	 * @test
	 */
	public function it_should_fire_tables_registered_action_when_successful(): void {
		$hook_fired = false;
		$prefix = Config::get_hook_prefix();

		add_action( "shepherd_{$prefix}_tables_registered", function() use ( &$hook_fired ) {
			$hook_fired = true;
		} );

		// Re-register to trigger the action
		$provider = new Provider( Config::get_container() );
		$provider->register();

		$this->assertTrue( $hook_fired, 'The tables_registered action should be fired' );
	}

	/**
	 * @test
	 */
	public function it_should_fire_error_action_on_database_exception(): void {
		$error_hook_fired = false;
		$registered_hook_fired = false;
		$caught_exception = null;
		$prefix = Config::get_hook_prefix();

		// Set up error action listener
		add_action( "shepherd_{$prefix}_tables_error", function( $exception ) use ( &$error_hook_fired, &$caught_exception ) {
			$error_hook_fired = true;
			$caught_exception = $exception;
		} );

		add_action( "shepherd_{$prefix}_tables_registered", function() use ( &$registered_hook_fired ) {
			$registered_hook_fired = true;
		} );

		// Mock Register::table to throw DatabaseQueryException
		$this->set_class_fn_return( Register::class, 'table', function() {
			throw new DatabaseQueryException( 'SELECT * FROM test', ['Test database error'], 'Test database error' );
		}, true );

		// Re-register to trigger the exception
		$provider = new Provider( Config::get_container() );
		$provider->register();

		$this->assertTrue( $error_hook_fired, 'The tables_error action should be fired on database exception' );
		$this->assertInstanceOf( DatabaseQueryException::class, $caught_exception, 'Exception should be DatabaseQueryException' );
		$this->assertEquals( 'Test database error', $caught_exception->getMessage() );
		$this->assertFalse( $registered_hook_fired, 'The tables_registered action should NOT be fired on database exception' );
	}
}
