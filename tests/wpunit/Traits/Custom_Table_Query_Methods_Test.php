<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Traits;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Abstracts\Table_Abstract;
use StellarWP\Schema\Register;
use StellarWP\Shepherd\Contracts\Model;
use StellarWP\Schema\Collections\Column_Collection;
use StellarWP\Schema\Columns\ID;
use StellarWP\Schema\Columns\String_Column;
use StellarWP\Schema\Tables\Table_Schema;

class Dummy_Query_Table extends Table_Abstract {
	use Custom_Table_Query_Methods;

	protected static $base_table_name = 'shepherd_query_table_%s';
	protected static $schema_slug = 'shepherd-query-table-%s';
	protected static $uid_column = 'id';

	const SCHEMA_VERSION = '0.0.1-dev';

	protected static $group = 'stellarwp_shepherd';

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

		Dummy_Query_Table::delete( (int) $row->id );
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
