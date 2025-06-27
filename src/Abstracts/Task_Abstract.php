<?php
/**
 * Pigeon's task abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Abstracts;

use InvalidArgumentException;
use StellarWP\Pigeon\Contracts\Task;
use StellarWP\Pigeon\Config;
use JsonSerializable;

/**
 * Pigeon's task abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Abstracts;
 */
abstract class Task_Abstract extends Task_Model_Abstract implements Task {
	/**
	 * The task's constructor.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$args The task's constructor arguments.
	 *
	 * @throws InvalidArgumentException If the task's constructor arguments are callable.
	 */
	public function __construct( ...$args ) {
		foreach ( $args as $arg ) {
			if ( is_callable( $arg ) ) {
				throw new InvalidArgumentException( 'Task constructor arguments must NOT be closures.' );
			}

			if ( is_object( $arg ) && ! $arg instanceof JsonSerializable ) {
				throw new InvalidArgumentException( 'Task constructor arguments should not be objects that are not JSON serializable.' );
			}
		}

		$this->set_args( $args );
		$this->validate_args();
		$this->set_class_hash();
		$this->set_data();
	}

	/**
	 * Gets the task's group.
	 *
	 * @since TBD
	 *
	 * @return string The task's group.
	 */
	public function get_group(): string {
		return sprintf( 'pigeon_%s_queue_default', Config::get_hook_prefix() );
	}

	/**
	 * Gets the task's priority.
	 *
	 * Action scheduler will not accept anything less than 0 or greater than 255.
	 *
	 * @since TBD
	 *
	 * @return int The task's priority.
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * Validates the task's arguments.
	 *
	 * @since TBD
	 */
	protected function validate_args(): void {}

	/**
	 * Gets the maximum number of retries.
	 *
	 * 0 means the task is not retryable, while less than 0 means the task is retryable indefinitely.
	 *
	 * @since TBD
	 *
	 * @return int The maximum number of retries.
	 */
	public function get_max_retries(): int {
		return 0;
	}

	/**
	 * Gets the retry delay.
	 *
	 * @since TBD
	 *
	 * @return int The retry delay in seconds.
	 */
	public function get_retry_delay(): int {
		return 30 * ( 2 ** ( $this->get_current_try() - 1 ) );
	}
}
