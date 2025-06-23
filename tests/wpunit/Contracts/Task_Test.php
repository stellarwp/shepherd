<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

use lucatume\WPBrowser\TestCase\WPTestCase;

class Task_Test extends WPTestCase {
	protected function get_task( ...$args ): Task {
		return new class( ...$args ) implements Task {

			public function __construct( ...$args ) {}

			public function process(): void {}

			public function set_id( int $id ): void {}

			public function set_args_hash( string $args_hash = '' ): void {}

			public function set_action_id( int $action_id ): void {}

			public function set_current_try( int $current_try ): void {}

			public function get_id(): int {
				return 0;
			}

			public function get_current_try(): int {
				return 0;
			}

			public function get_args_hash(): string {
				return '';
			}

			public function get_action_id(): int {
				return 0;
			}

			public function get_args(): array {
				return [];
			}

			public function get_group(): string {
				return '';
			}

			public function is_unique(): bool {
				return false;
			}

			public function get_priority(): int {
				return 0;
			}

			public function is_retryable(): bool {
				return false;
			}

			public function should_retry(): bool {
				return false;
			}

			public function get_retry_delay(): int {
				return 0;
			}

			public function is_debouncable(): bool {
				return false;
			}

			public function get_debounce_delay(): int {
				return 0;
			}
		};
	}

	/**
	 * @test
	 */
	public function it_should_be_compatible_after_updates(): void {
		$task = $this->get_task( 'test1', 3, 'test2' );

		$this->assertInstanceOf( Task::class, $task );

		$task->process();
		$task->set_id( 1 );
		$task->set_args_hash( 'test1' );
		$task->set_action_id( 1 );
		$task->set_current_try( 1 );
		$task->get_args();
		$task->get_group();
		$task->is_unique();
		$task->get_priority();
		$task->is_retryable();
		$task->should_retry();
		$task->get_retry_delay();
		$task->is_debouncable();
		$task->get_debounce_delay();
	}
}
