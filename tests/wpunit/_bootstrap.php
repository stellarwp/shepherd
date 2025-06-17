<?php

use StellarWP\Pigeon\Contracts\Container;
use StellarWP\Pigeon\Tables\Tasks;
use StellarWP\Pigeon\Provider;

Provider::set_hook_prefix( tests_pigeon_get_hook_prefix() );

tests_pigeon_drop_tables();

// Bootstrap Pigeon.
tests_pigeon_get_container()->register( Provider::class );

function tests_pigeon_get_container(): Container {
	static $container = null;

	if ( null === $container ) {
		$container = new Container();
	}

	return $container;
}

// Drop the tables after the tests are done.
tests_add_filter(
	'shutdown',
	'tests_pigeon_drop_tables'
);

/**
 * Drop the tables before and after the suite.
 *
 * @return void
 */
function tests_pigeon_drop_tables() {
	tests_pigeon_get_container()->get( Tasks::class )->drop();
}

/**
 * Get the hook prefix.
 *
 * @return string
 */
function tests_pigeon_get_hook_prefix(): string {
	return 'foobar';
}
