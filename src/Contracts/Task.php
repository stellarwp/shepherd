<?php
/**
 * Shepherd's task contract.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Contracts;

/**
 * Shepherd's task contract.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd
 */
interface Task extends Task_Model {
	/**
	 * Processes the task.
	 *
	 * @since TBD
	 */
	public function process(): void;

	/**
	 * Gets the task's group.
	 *
	 * @since TBD
	 *
	 * @return string The task's group.
	 */
	public function get_group(): string;

	/**
	 * Gets the task's priority.
	 *
	 * @since TBD
	 *
	 * @return int The task's priority.
	 */
	public function get_priority(): int;

	/**
	 * Gets the maximum number of retries.
	 *
	 * 0 means the task is not retryable, while less than 0 means the task is retryable indefinitely.
	 *
	 * @since TBD
	 *
	 * @return int The maximum number of retries.
	 */
	public function get_max_retries(): int;

	/**
	 * Gets the task's retry delay.
	 *
	 * @since TBD
	 *
	 * @return int The task's retry delay in seconds.
	 */
	public function get_retry_delay(): int;
}
