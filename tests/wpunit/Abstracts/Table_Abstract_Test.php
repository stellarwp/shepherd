<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Abstracts;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Contracts\Model;
use StellarWP\Shepherd\Tables\Utility\Safe_Dynamic_Prefix;
use StellarWP\DB\DB;
use StellarWP\Schema\Collections\Column_Collection;
use StellarWP\Schema\Columns\ID;
use StellarWP\Schema\Columns\String_Column;
use StellarWP\Schema\Tables\Table_Schema;

class Dummy_Table extends Table_Abstract {
	protected static $base_table_name = 'pi_%s_dummy_table';
	protected static $schema_slug = 'shepherd-%s-dummy-table';
	protected static $uid_column = 'id';

	const SCHEMA_VERSION = '0.0.1-test';

	public static function get_schema_history(): array {
		$table_name = static::table_name( true );

		return [
			static::SCHEMA_VERSION => function() use ( $table_name ) {
				$columns = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = new String_Column( 'name' );
				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	public static function transform_from_array( array $model_array ) {
		return $model_array;
	}
}

class Table_Abstract_Test extends WPTestCase {
	/**
	 * @before
	 */
	public function set_config_prefix(): void {
		Config::set_hook_prefix( 'test' );
	}

	/**
	 * @after
	 */
	public function reset_config(): void {
		Config::set_hook_prefix( tests_shepherd_get_hook_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_get_correct_table_name_and_slug() {
		$this->assertEquals( 'wp_pi_tes_dummy_table', Dummy_Table::table_name() );
		$this->assertEquals( 'shepherd-test-dummy-table', Dummy_Table::get_schema_slug() );
	}

	/**
	 * @test
	 */
	public function it_should_generate_correct_table_definition() {
		$table = new Dummy_Table();
		$definition = $table->get_definition();

		$this->assertStringContainsString( 'CREATE TABLE `wp_pi_tes_dummy_table`', $definition );
		$this->assertStringContainsString( '`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT', $definition );
		$this->assertStringContainsString( '`name` varchar(255) NOT NULL', $definition );
		$this->assertStringContainsString( 'PRIMARY KEY (id)', $definition );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_searchable_columns() {
		$this->assertEquals( [], Dummy_Table::get_searchable_columns()->get_names() );
	}

	/**
	 * @test
	 */
	public function it_should_trim_long_hook_prefix_to_prevent_exceeding_mysql_table_name_limit() {
		// Set a very long hook prefix that would exceed MySQL's 64-character limit
		$long_prefix = 'this_is_a_very_long_hook_prefix_that_would_definitely_exceed_the_mysql_limit';
		Config::set_hook_prefix( $long_prefix );

		// Create a new instance to pick up the new prefix
		new Dummy_Table();

		// The table name should use the safe prefix
		$table_name = Dummy_Table::table_name();
		$safe_prefix = Config::get_container()->get( Safe_Dynamic_Prefix::class )->get();

		// The safe prefix should be trimmed
		$this->assertLessThan( strlen( $long_prefix ), strlen( $safe_prefix ), 'Safe prefix should be shorter than original' );

		// The table name should match expected format
		$expected = DB::prefix( 'pi_' . $safe_prefix . '_dummy_table' );
		$this->assertEquals( $expected, $table_name );

		$this->assertLessThanOrEqual( 64, strlen( $table_name ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_trim_short_hook_prefix() {
		// Set a short hook prefix that won't exceed the limit
		$short_prefix = 'sho';
		Config::set_hook_prefix( $short_prefix );

		$table_name = Dummy_Table::table_name();

		// The table name should contain the full hook prefix
		$this->assertEquals( 'wp_pi_sho_dummy_table', $table_name );
	}
}
