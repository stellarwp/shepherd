<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Abstracts\Table_Abstract;
use StellarWP\Pigeon\Config;
use StellarWP\DB\DB;
use StellarWP\Schema\Register;

class Dummy_Query_Table extends Table_Abstract {
	use Custom_Table_Query_Methods;

	protected static $base_table_name = 'pigeon_query_table_%s';
	protected static $schema_slug = 'pigeon-query-table-%s';
	protected static $uid_column = 'id';

	public static function get_columns(): array {
		return [
			'id'   => [ 'type' => self::COLUMN_TYPE_BIGINT, 'unsigned' => true, 'auto_increment' => true, 'php_type' => self::PHP_TYPE_INT ],
			'name' => [ 'type' => self::COLUMN_TYPE_VARCHAR, 'length' => 255, 'php_type' => self::PHP_TYPE_STRING ],
			'email' => [ 'type' => self::COLUMN_TYPE_VARCHAR, 'length' => 255, 'php_type' => self::PHP_TYPE_STRING ],
		];
	}
}

class Custom_Table_Query_Methods_Test extends WPTestCase {
	/**
	 * @before
	 */
	public function install_tables(): void {
		Register::table( Dummy_Query_Table::class );
	}

	/**
	 * @after
	 */
	public function drop_tables(): void {
		Register::remove_table( Dummy_Query_Table::class );
	}

	/**
	 * @test
	 */
	public function it_should_insert_and_fetch_rows() {
		Dummy_Query_Table::insert( [ 'name' => 'John Doe', 'email' => 'john@test.com' ] );
		Dummy_Query_Table::insert_many( [
			[ 'name' => 'Jane Doe', 'email' => 'jane@test.com' ],
			[ 'name' => 'Peter Pan', 'email' => 'peter@test.com' ],
		] );

		$results = iterator_to_array( Dummy_Query_Table::fetch_all() );
		$this->assertCount( 3, $results );
		$this->assertEquals( 'Jane Doe', $results[1]->name );
	}

	/**
	 * @test
	 */
	public function it_should_update_rows() {
		Dummy_Query_Table::insert( [ 'name' => 'John Doe', 'email' => 'john@test.com' ] );
		$row = Dummy_Query_Table::fetch_first_where( "WHERE name = 'John Doe'" );

		Dummy_Query_Table::update_single( [ 'id' => $row->id, 'name' => 'John Smith' ] );
		$updated_row = Dummy_Query_Table::fetch_first_where( "WHERE id = {$row->id}" );
		$this->assertEquals( 'John Smith', $updated_row->name );
	}

	/**
	 * @test
	 */
	public function it_should_delete_rows() {
		Dummy_Query_Table::insert( [ 'name' => 'John Doe', 'email' => 'john@test.com' ] );
		$row = Dummy_Query_Table::fetch_first_where( "WHERE name = 'John Doe'" );

		Dummy_Query_Table::delete( $row->id );
		$this->assertNull( Dummy_Query_Table::fetch_first_where( "WHERE id = {$row->id}" ) );
	}

	/**
	 * @test
	 */
	public function it_should_upsert_rows() {
		// Test insert
		Dummy_Query_Table::upsert( [ 'name' => 'John Doe', 'email' => 'john@test.com' ] );
		$row = Dummy_Query_Table::fetch_first_where( "WHERE name = 'John Doe'" );
		$this->assertNotNull( $row );

		// Test update
		Dummy_Query_Table::upsert( [ 'id' => $row->id, 'name' => 'John Smith' ] );
		$updated_row = Dummy_Query_Table::fetch_first_where( "WHERE id = {$row->id}" );
		$this->assertEquals( 'John Smith', $updated_row->name );
	}

	/**
	 * @test
	 */
	public function it_should_paginate_results() {
		for ( $i = 0; $i < 10; $i++ ) {
			Dummy_Query_Table::insert( [ 'name' => "User {$i}", 'email' => "user{$i}@test.com" ] );
		}

		$page1 = Dummy_Query_Table::paginate( [], 5, 1 );
		$this->assertCount( 5, $page1 );
		$this->assertEquals( 'User 0', $page1[0]->name );

		$page2 = Dummy_Query_Table::paginate( [], 5, 2 );
		$this->assertCount( 5, $page2 );
		$this->assertEquals( 'User 5', $page2[0]->name );
	}
}
