<?php
use StellarWP\Pigeon\Tests\Container;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Pigeon\Tables\Tasks;
use StellarWP\Pigeon\Tables\Task_Logs;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Provider;
use StellarWP\Pigeon\Tables;
use StellarWP\Schema\Register;

/**
 * Drop the tables before and after the suite.
 *
 * @return void
 */
function tests_pigeon_drop_tables() {
	$container           = tests_pigeon_get_container();
	$safe_dynamic_prefix = $container->get( Tables\Utility\Safe_Dynamic_Prefix::class );

	$tables        = [];
	$table_classes = [
		Tasks::class,
		Task_Logs::class,
	];

	$longest_table_name = $safe_dynamic_prefix->get_longest_table_name( $table_classes );

	foreach ( $table_classes as $table_class ) {
		$tables[] = sprintf( $table_class::raw_base_table_name(), $safe_dynamic_prefix->get( $longest_table_name ) );
	}

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
 * Raises the auto increment for the tables.
 *
 * @return void
 */
function tests_pigeon_raise_auto_increment(): void {
	DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 86740', DB::prefix( 'actionscheduler_actions' ) ) );
	DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 94540', DB::prefix( 'actionscheduler_logs' ) ) );

	$tables = [
		Tasks::base_table_name(),
		Task_Logs::base_table_name(),
	];

	foreach ( $tables as $table ) {
		DB::query(
			DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = %d', DB::prefix( $table ), 9567492 )
		);
	}
}

/**
 * Resets the config.
 *
 * @return void
 */
function tests_pigeon_reset_config(): void {
	Config::reset();
	Config::set_hook_prefix( tests_pigeon_get_hook_prefix() );
	Config::set_container( tests_pigeon_get_container() );
	Config::set_max_table_name_length( 25 );
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
 * Gets a DateTime instance for the current time.
 *
 * @return DateTimeInterface
 */
function tests_pigeon_get_dt(): DateTimeInterface {
	return new DateTime( '2025-06-13 17:25:00', new DateTimeZone( 'UTC' ) );
}

/**
 * Bootstraps the common test environment.
 *
 * @return void
 */
function tests_pigeon_common_bootstrap(): void {
	tests_pigeon_reset_config();
	tests_pigeon_drop_tables();

	$container = Config::get_container();

	// Bootstrap Pigeon.
	$container->singleton( Provider::class );
	$container->get( Provider::class )->register();

	// For tests we forcefully register the task logs table for it to exist.
	Register::table( Task_Logs::class );

	tests_pigeon_raise_auto_increment();

	// Drop the tables after the tests are done.
	tests_add_filter(
		'shutdown',
		'tests_pigeon_drop_tables'
	);
}