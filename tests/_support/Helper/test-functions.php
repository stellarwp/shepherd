<?php
use StellarWP\Pigeon\Tests\Container;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Pigeon\Tables\Tasks;
use StellarWP\Pigeon\Tables\Task_Logs;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Provider;
use StellarWP\Pigeon\Config;

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

	$as_tables = DB::get_results( DB::prepare( 'SHOW TABLES LIKE %s', DB::prefix( 'actionscheduler_%' ) ) );

	foreach ( $as_tables as $table ) {
		$prop = array_values( get_object_vars( $table ) )['0'];
		DB::query( DB::prepare( 'TRUNCATE TABLE %i', $prop ) );
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

/**
 * Bootstraps the common test environment.
 *
 * @return void
 */
function tests_pigeon_common_bootstrap(): void {
	Config::set_hook_prefix( tests_pigeon_get_hook_prefix() );

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
}