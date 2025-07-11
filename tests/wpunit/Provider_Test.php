<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\DB\DB;

class Provider_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @test
	 */
	public function it_should_assert_that_the_provider_is_not_registered(): void {
		$this->assertTrue( Provider::is_registered() );
	}

	/**
	 * @test
	 */
	public function it_should_evaluate_hook_prefix(): void {
		$this->assertEquals( tests_shepherd_get_hook_prefix(), Config::get_hook_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_register_action_deletion_hook(): void {
		$provider = Config::get_container()->get( Provider::class );
		
		// Check that the action_scheduler_deleted_action hook is registered
		$this->assertTrue( has_action( 'action_scheduler_deleted_action', [ $provider, 'delete_tasks_on_action_deletion' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_delete_tasks_on_action_deletion_when_tasks_exist(): void {
		$provider = Config::get_container()->get( Provider::class );
		$action_id = 123;
		
		// Mock task IDs that would be found
		$task_ids = [ '1', '2', '3' ];
		$this->set_fn_return( [ DB::class, 'get_col' ], $task_ids );
		
		// Track DB queries
		$queries = [];
		$this->set_fn_return( [ DB::class, 'query' ], function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return true;
		} );
		
		$provider->delete_tasks_on_action_deletion( $action_id );
		
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
	public function it_should_not_delete_when_no_tasks_exist_for_action(): void {
		$provider = Config::get_container()->get( Provider::class );
		$action_id = 456;
		
		// Mock empty result
		$this->set_fn_return( [ DB::class, 'get_col' ], [] );
		
		// Track DB queries
		$queries = [];
		$this->set_fn_return( [ DB::class, 'query' ], function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return true;
		} );
		
		$provider->delete_tasks_on_action_deletion( $action_id );
		
		// Should not have executed any DELETE queries
		$this->assertCount( 0, $queries );
	}

	/**
	 * @test
	 */
	public function it_should_sanitize_task_ids_before_deletion(): void {
		$provider = Config::get_container()->get( Provider::class );
		$action_id = 789;
		
		// Mock task IDs with duplicates and mixed types
		$mixed_task_ids = [ '1', '2', '2', '3', 1, 2 ];
		$this->set_fn_return( [ DB::class, 'get_col' ], $mixed_task_ids );
		
		// Track DB queries
		$queries = [];
		$this->set_fn_return( [ DB::class, 'query' ], function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return true;
		} );
		
		$provider->delete_tasks_on_action_deletion( $action_id );
		
		// Should have deduplicated the IDs
		$this->assertStringContainsString( 'task_id IN (1,2,3)', $queries[0] );
		$this->assertStringContainsString( 'IN (1,2,3)', $queries[1] );
	}

	/**
	 * @test
	 */
	public function it_should_query_for_tasks_with_correct_action_id(): void {
		$provider = Config::get_container()->get( Provider::class );
		$action_id = 999;
		
		// Track DB queries
		$queries = [];
		$this->set_fn_return( [ DB::class, 'get_col' ], function ( $query ) use ( &$queries ) {
			$queries[] = $query;
			return [];
		} );
		
		$provider->delete_tasks_on_action_deletion( $action_id );
		
		// Should have queried for the correct action ID
		$this->assertCount( 1, $queries );
		$this->assertStringContainsString( 'action_id = 999', $queries[0] );
		$this->assertStringContainsString( Tasks::table_name(), $queries[0] );
	}
}
