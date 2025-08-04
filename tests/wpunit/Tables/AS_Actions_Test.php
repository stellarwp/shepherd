<?php
/**
 * Tests for the AS_Actions table interface.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Tables
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tables;

use lucatume\WPBrowser\TestCase\WPTestCase;

class AS_Actions_Test extends WPTestCase {

	/**
	 * @test
	 */
	public function it_should_have_correct_base_table_name(): void {
		$this->assertEquals( 'actionscheduler_actions', AS_Actions::table_name( false ) );
	}

	/**
	 * @test
	 */
	public function it_should_have_correct_uid_column(): void {
		$this->assertEquals( 'action_id', AS_Actions::uid_column() );
	}

	/**
	 * @test
	 */
	public function it_should_return_expected_columns(): void {
		$columns = AS_Actions::get_columns();

		$this->assertArrayHasKey( 'action_id', $columns );
		$this->assertArrayHasKey( 'status', $columns );

		// Check action_id column configuration
		$action_id_config = $columns['action_id'];
		$this->assertEquals( AS_Actions::COLUMN_TYPE_BIGINT, $action_id_config['type'] );
		$this->assertEquals( AS_Actions::PHP_TYPE_INT, $action_id_config['php_type'] );
		$this->assertEquals( 20, $action_id_config['length'] );
		$this->assertTrue( $action_id_config['unsigned'] );
		$this->assertTrue( $action_id_config['auto_increment'] );
		$this->assertFalse( $action_id_config['nullable'] );

		// Check status column configuration
		$status_config = $columns['status'];
		$this->assertEquals( AS_Actions::COLUMN_TYPE_VARCHAR, $status_config['type'] );
		$this->assertEquals( AS_Actions::PHP_TYPE_STRING, $status_config['php_type'] );
		$this->assertEquals( 20, $status_config['length'] );
		$this->assertFalse( $status_config['nullable'] );
	}

	/**
	 * @test
	 */
	public function it_should_return_searchable_columns(): void {
		$searchable = AS_Actions::get_searchable_columns();

		$this->assertCount( 1, $searchable );
		$this->assertContains( 'status', $searchable );
	}

	/**
	 * @test
	 */
	public function it_should_return_table_name_with_correct_prefix(): void {
		global $wpdb;

		$expected = $wpdb->prefix . 'actionscheduler_actions';
		$actual = AS_Actions::table_name();

		$this->assertEquals( $expected, $actual );
	}
}
