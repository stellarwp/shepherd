<?php
use StellarWP\Shepherd\Tests\Container;
use StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\DB\DB;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Provider;
use StellarWP\Shepherd\Tables;
use StellarWP\Schema\Register;

/**
 * Drop the tables before and after the suite.
 *
 * @return void
 */
function tests_shepherd_drop_tables() {
	$container           = tests_shepherd_get_container();
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
function tests_shepherd_raise_auto_increment(): void {
	DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 86740', DB::prefix( 'actionscheduler_actions' ) ) );
	DB::query( DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = 94540', DB::prefix( 'actionscheduler_logs' ) ) );

	$tables = [
		Tasks::base_table_name(),
		Task_Logs::base_table_name(),
	];

	foreach ( $tables as $offset => $table ) {
		DB::query(
			DB::prepare( 'ALTER TABLE %i AUTO_INCREMENT = %d', DB::prefix( $table ), 728365 + ( 1 + (int) $offset * 3 ) )
		);
	}
}

/**
 * Resets the config.
 *
 * @return void
 */
function tests_shepherd_reset_config(): void {
	Config::reset();
	Config::set_hook_prefix( tests_shepherd_get_hook_prefix() );
	Config::set_container( tests_shepherd_get_container() );
	Config::set_max_table_name_length( 25 );
}

/**
 * Get the hook prefix.
 *
 * @return string
 */
function tests_shepherd_get_hook_prefix(): string {
	return 'foobar';
}

/**
 * Gets a container instance for tests.
 *
 * @return ContainerInterface
 */
function tests_shepherd_get_container(): ContainerInterface {
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
function tests_shepherd_get_dt(): DateTimeInterface {
	return new DateTime( '2025-06-13 17:25:00', new DateTimeZone( 'UTC' ) );
}

/**
 * Enables fake transactions.
 *
 * @return void
 */
function tests_shepherd_fake_transactions_enable() {
	uopz_set_return( DB::class, 'beginTransaction', true, false );
	uopz_set_return( DB::class, 'rollback', true, false );
	uopz_set_return( DB::class, 'commit', true, false );
}

/**
 * Disables fake transactions.
 *
 * @return void
 */
function tests_shepherd_fake_transactions_disable() {
	uopz_unset_return( DB::class, 'beginTransaction' );
	uopz_unset_return( DB::class, 'rollback' );
	uopz_unset_return( DB::class, 'commit' );
}

/**
 * Bootstraps the common test environment.
 *
 * @return void
 */
function tests_shepherd_common_bootstrap(): void {
	tests_shepherd_reset_config();
	tests_shepherd_drop_tables();
	tests_shepherd_fake_transactions_enable();

	$container = Config::get_container();

	add_action( 'shepherd_' . Config::get_hook_prefix() . '_tables_error', '__return_true' );

	// Bootstrap Shepherd.
	$container->singleton( Provider::class );
	$container->get( Provider::class )->register();

	// For tests we forcefully register the task logs table for it to exist.
	Register::table( Task_Logs::class );

	tests_shepherd_raise_auto_increment();

	// Drop the tables after the tests are done.
	tests_add_filter(
		'shutdown',
		'tests_shepherd_drop_tables'
	);
}
