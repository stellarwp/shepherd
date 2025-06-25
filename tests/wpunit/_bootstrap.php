<?php

use StellarWP\Pigeon\Tests\Container;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Pigeon\Tables\Tasks;
use StellarWP\Pigeon\Tables\Task_Logs;
use StellarWP\Pigeon\Provider;
use StellarWP\DB\DB;

Provider::set_hook_prefix( tests_pigeon_get_hook_prefix() );

$container = tests_pigeon_get_container();

tests_pigeon_drop_tables();

// Bootstrap Pigeon.
$container->singleton( Provider::class );
$container->get( Provider::class )->register();

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
	$tables = [
		Tasks::base_table_name(),
		Task_Logs::base_table_name(),
	];

	foreach ( $tables as $table ) {
		DB::query(
			DB::prepare( 'DROP TABLE IF EXISTS %i', DB::prefix( $table ) )
		);
	}
}

/**
 * Get the hook prefix.
 *
 * @return string
 */
function tests_pigeon_get_hook_prefix(): string {
	return 'foobar';
}

/**
 * Gets a container instance for tests.
 *
 * @return ContainerInterface
 */
function tests_pigeon_get_container(): ContainerInterface {
	static $container = null;

	if ( null === $container ) {
		$container = new Container();
		$container->bind( ContainerInterface::class, $container );
	}

	return $container;
}
