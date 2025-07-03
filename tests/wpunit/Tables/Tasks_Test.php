<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tables;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Task;
use StellarWP\Pigeon\Abstracts\Task_Abstract;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Config;
use InvalidArgumentException;

class Dummy_Task extends Task_Abstract implements Task {
	public $arg1;
	public $arg2;

	public function __construct( $arg1, $arg2 ) {
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
		parent::__construct();
	}

	public function get_args(): array {
		return [ 'arg1' => $this->arg1, 'arg2' => $this->arg2 ];
	}

	public function process(): void {
		// Do nothing.
	}

	public function get_task_prefix(): string {
		return 'dummy';
	}
}

class Tasks_Test extends WPTestCase {
	private function insert_dummy_task( array $data ) {
		$table_name = Tasks::table_name();
		$defaults = [
			'action_id'   => 1,
			'class_hash'  => md5( Dummy_Task::class ),
			'args_hash'   => md5( serialize( [ 'arg1' => 'val1', 'arg2' => 'val2' ] ) ),
			'data'        => json_encode( [
				'task_class' => Dummy_Task::class,
				'args'       => [ 'val1', 'val2' ],
			] ),
			'current_try' => 1,
		];
		$data = array_merge( $defaults, $data );

		DB::insert( $table_name, $data );
	}

	/**
	 * @test
	 */
	public function it_should_be_using_the_prefix(): void {
		$name = Tasks::base_table_name();
		$this->assertStringContainsString( Config::get_hook_prefix(), $name );

		$query = DB::prepare( 'SHOW TABLES LIKE %s', DB::prefix( $name ) );
		$tables = DB::get_results( $query );

		$this->assertNotEmpty( $tables );
		$this->assertCount( 1, $tables );
	}

	/**
	 * @test
	 */
	public function it_should_get_task_by_action_id() {
		$this->insert_dummy_task( [ 'action_id' => 123 ] );
		$task = Tasks::get_by_action_id( 123 );

		$this->assertInstanceOf( Dummy_Task::class, $task );
		$this->assertEquals( 'val1', $task->arg1 );
	}

	/**
	 * @test
	 */
	public function it_should_return_null_if_task_not_found_by_action_id() {
		$this->assertNull( Tasks::get_by_action_id( 999 ) );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_non_existent_class() {
		$this->insert_dummy_task( [
			'action_id' => 124,
			'data'      => json_encode( [ 'task_class' => 'NonExistentTask', 'args' => [] ] ),
		] );

		$this->expectException( InvalidArgumentException::class );
		Tasks::get_by_action_id( 124 );
	}

	/**
	 * @test
	 */
	public function it_should_get_tasks_by_args_hash() {
		$args_hash = md5( serialize( [ 'arg1' => 'hash_val', 'arg2' => 'hash_val2' ] ) );
		$this->insert_dummy_task( [ 'action_id' => 125, 'args_hash' => $args_hash ] );
		$this->insert_dummy_task( [ 'action_id' => 126, 'args_hash' => $args_hash ] );

		$tasks = Tasks::get_by_args_hash( $args_hash );
		$this->assertCount( 2, $tasks );
		$this->assertInstanceOf( Dummy_Task::class, $tasks[0] );
	}

	/**
	 * @test
	 */
	public function it_should_handle_very_long_hook_prefix_without_exceeding_mysql_limit() {
		$long_prefix = 'this_is_an_extremely_long_hook_prefix_that_would_normally_cause_mysql_errors';
		Config::set_hook_prefix( $long_prefix );

		// Get the table name - it should be trimmed to fit within MySQL's limit
		$table_name = Tasks::table_name();

		$this->assertEquals( 64, strlen( $table_name ), 'Tasks table name should not exceed MySQL\'s 64-character limit' );

		$this->assertEquals( substr( DB::prefix( 'pigeon_tasks_' . $long_prefix ), 0, 64 ), $table_name );
	}

	/**
	 * @after
	 */
	public function reset() {
		Config::set_hook_prefix( tests_pigeon_get_hook_prefix() );
	}
}
