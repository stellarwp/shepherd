<?php
/**
 * Pigeon's task contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

/**
 * Pigeon's task contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
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
	 * Checks if the task is unique.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task is unique.
	 */
	public function is_unique(): bool;

	/**
	 * Gets the task's priority.
	 *
	 * @since TBD
	 *
	 * @return int The task's priority.
	 */
	public function get_priority(): int;

	/**
	 * Checks if the task is retryable.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task is retryable.
	 */
	public function is_retryable(): bool;

	/**
	 * Checks if the task should be retried.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task should be retried.
	 */
	public function should_retry(): bool;

	/**
	 * Gets the task's retry delay.
	 *
	 * @since TBD
	 *
	 * @return int The task's retry delay in seconds.
	 */
	public function get_retry_delay(): int;

	/**
	 * Checks if the task is debouncable.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task is debouncable.
	 */
	public function is_debouncable(): bool;

	/**
	 * Gets the task's debounce delay.
	 *
	 * @since TBD
	 *
	 * @return int The task's debounce delay in seconds.
	 */
	public function get_debounce_delay(): int;

	/**
	 * Gets the task's debounce delay on failure.
	 *
	 * @since TBD
	 *
	 * @return int The task's debounce delay on failure in seconds.
	 */
	public function get_debounce_delay_on_failure(): int;
}
