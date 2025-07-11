<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Contracts;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Abstracts\Table_Abstract;


class Task_Test extends WPTestCase {
	protected function get_task( ...$args ): Task {
		return new class( ...$args ) implements Task {

			public function process(): void {}

			public function save(): int {
				return 0;
			}

			public function delete(): void {}

			public function get_table_interface(): Table_Abstract {
				return new class() extends Table_Abstract {
					public static function get_columns(): array {
						return [];
					}
				};
			}

			public function to_array(): array {
				return [];
			}

			public function set_data(): void {}

			public function get_data(): string {
				return '';
			}

			public function set_id( int $id ): void {}

			public function set_args_hash(): void {}

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

			public function get_priority(): int {
				return 0;
			}

			public function get_max_retries(): int {
				return 0;
			}

			public function get_retry_delay(): int {
				return 0;
			}

			public function get_task_prefix(): string {
				return '';
			}

			public function get_class_hash(): string {
				return '';
			}

			public function set_class_hash(): void {}

			public function set_args( array $args ): void {}
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
		$task->set_args_hash();
		$task->set_action_id( 1 );
		$task->set_current_try( 1 );
		$task->get_args();
		$task->get_group();
		$task->get_priority();
		$task->get_max_retries();
		$task->get_retry_delay();
		$task->get_task_prefix();
		$task->get_data();
		$task->get_class_hash();
		$task->get_args_hash();
		$task->get_action_id();
		$task->get_current_try();
		$task->get_id();
		$task->set_data();
		$task->set_class_hash();
		$task->set_args( [ 'test1', 3, 'test2' ] );
	}
}
