<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Abstracts;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Config;
use StellarWP\DB\DB;

class Dummy_Table extends Table_Abstract {
	protected static $base_table_name = 'pigeon_dummy_table_%s';
	protected static $schema_slug = 'pigeon-dummy-table-%s';
	protected static $uid_column = 'id';

	public static function get_columns(): array {
		return [
			'id'   => [ 'type' => self::COLUMN_TYPE_BIGINT, 'length' => 20, 'unsigned' => true, 'auto_increment' => true ],
			'name' => [ 'type' => self::COLUMN_TYPE_VARCHAR, 'length' => 255 ],
		];
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
		Config::set_hook_prefix( tests_pigeon_get_hook_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_get_correct_table_name_and_slug() {
		$this->assertEquals( 'wp_pigeon_dummy_table_test', Dummy_Table::table_name() );
		$this->assertEquals( 'pigeon-dummy-table-test', Dummy_Table::get_schema_slug() );
	}

	/**
	 * @test
	 */
	public function it_should_generate_correct_table_definition() {
		$table = new Dummy_Table();
		$definition = $table->get_definition();

		$this->assertStringContainsString( 'CREATE TABLE `wp_pigeon_dummy_table_test`', $definition );
		$this->assertStringContainsString( '`id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT', $definition );
		$this->assertStringContainsString( '`name` varchar(255) NOT NULL', $definition );
		$this->assertStringContainsString( 'PRIMARY KEY (`id`)', $definition );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_searchable_columns() {
		$this->assertEquals( [], Dummy_Table::get_searchable_columns() );
	}
}
