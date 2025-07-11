<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\DB\DB;

class Herding_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @test
	 */
	public function it_should_return_correct_task_prefix() {
		$herding = new Herding();
		$this->assertEquals( 'shepherd_tidy_', $herding->get_task_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_process_without_error_when_no_orphaned_tasks() {
		$herding = new Herding();
		
		// Mock the DB queries to return empty results
		$this->set_fn_return( [ DB::class, 'get_col' ], [] );
		
		// Should not throw any exceptions
		$herding->process();
		
		// Verify process completed successfully
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function it_should_delete_orphaned_tasks_and_logs() {
		$herding = new Herding();
		
		// Mock orphaned task IDs
		$orphaned_task_ids = [ '1', '2', '3' ];
		$this->set_fn_return( [ DB::class, 'get_col' ], $orphaned_task_ids );
		
		// Track DB queries
		$queries = [];
		$this->set_fn_return( [ DB::class, 'query' ], function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return true;
		} );
		
		$herding->process();
		
		// Should have executed 2 DELETE queries
		$this->assertCount( 2, $queries );
		
		// First query should delete from task logs
		$this->assertStringContainsString( 'DELETE FROM', $queries[0] );
		$this->assertStringContainsString( Task_Logs::table_name(), $queries[0] );
		$this->assertStringContainsString( 'task_id IN (1,2,3)', $queries[0] );
		
		// Second query should delete from tasks
		$this->assertStringContainsString( 'DELETE FROM', $queries[1] );
		$this->assertStringContainsString( Tasks::table_name(), $queries[1] );
		$this->assertStringContainsString( 'IN (1,2,3)', $queries[1] );
	}

	/**
	 * @test
	 */
	public function it_should_fire_action_hook_after_processing() {
		$herding = new Herding();
		
		// Mock empty orphaned tasks
		$this->set_fn_return( [ DB::class, 'get_col' ], [] );
		
		// Track action hook calls
		$hook_called = false;
		$hook_task = null;
		
		add_action( 'shepherd_test_herding_processed', function( $task ) use ( &$hook_called, &$hook_task ) {
			$hook_called = true;
			$hook_task = $task;
		} );
		
		// Mock the Config::get_hook_prefix() to return 'test'
		$this->set_fn_return( [ \StellarWP\Shepherd\Config::class, 'get_hook_prefix' ], 'test' );
		
		$herding->process();
		
		$this->assertTrue( $hook_called );
		$this->assertSame( $herding, $hook_task );
	}

	/**
	 * @test
	 */
	public function it_should_sanitize_task_ids_before_deletion() {
		$herding = new Herding();
		
		// Mock task IDs with duplicates and mixed types
		$mixed_task_ids = [ '1', '2', '2', '3', 1, 2 ];
		$this->set_fn_return( [ DB::class, 'get_col' ], $mixed_task_ids );
		
		// Track DB queries
		$queries = [];
		$this->set_fn_return( [ DB::class, 'query' ], function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return true;
		} );
		
		$herding->process();
		
		// Should have deduplicated the IDs
		$this->assertStringContainsString( 'task_id IN (1,2,3)', $queries[0] );
		$this->assertStringContainsString( 'IN (1,2,3)', $queries[1] );
	}
}