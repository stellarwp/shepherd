<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Abstracts;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Pigeon\Contracts\Task;

class Task_Test extends WPTestCase {
	protected function get_task( ...$args ): Task {
		return new class( ...$args ) extends Task_Abstract {
			public function process(): void {}

			public function get_task_prefix(): string {
				return 'test_';
			}
		};
	}

	/**
	 * @test
	 */
	public function it_should_be_compatible_after_updates(): void {
		$task = $this->get_task( 'test1', 3, 'test2' );

		$this->assertInstanceOf( Task::class, $task );
		$this->assertInstanceOf( Task_Abstract::class, $task );

		$prefix = tests_pigeon_get_hook_prefix();

		$this->assertSame( [ 'test1', 3, 'test2' ], $task->get_args() );
		$this->assertSame( "pigeon_{$prefix}_queue_default", $task->get_group() );
		$this->assertFalse( $task->is_unique() );
		$this->assertSame( 10, $task->get_priority() );

		$this->assertIsInt( $task->get_id() );
		$this->assertIsInt( $task->get_action_id() );
		$this->assertIsInt( $task->get_current_try() );
		$this->assertIsString( $task->get_args_hash() );

		$task->set_args_hash( md5( wp_json_encode( [ 'test1', 3, 'test2' ] ) ) );
		$task->set_action_id( 1 );
		$task->set_current_try( 2 );
		$task->set_id( 3 );

		$this->assertSame( 3, $task->get_id() );
		$this->assertSame( 1, $task->get_action_id() );
		$this->assertSame( 2, $task->get_current_try() );
		$this->assertSame( $task->get_task_prefix() . md5( wp_json_encode( [ get_class( $task ), 'test1', 3, 'test2' ] ) ), $task->get_args_hash() );

		$this->assertFalse( $task->is_retryable() );
		$this->assertFalse( $task->should_retry() );
		$this->assertSame( 0, $task->get_retry_delay() );
		$this->assertTrue( $task->is_debouncable() );
		$this->assertSame( 0, $task->get_debounce_delay() );
	}
}
