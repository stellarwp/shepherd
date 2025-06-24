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
interface Task {
	/**
	 * Processes the task.
	 *
	 * @since TBD
	 */
	public function process(): void;

	/**
	 * Sets the task's ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The task's ID.
	 */
	public function set_id( int $id ): void;

	/**
	 * Sets the task's arguments hash.
	 *
	 * @since TBD
	 *
	 * @param string $args_hash The task's arguments hash.
	 */
	public function set_args_hash( string $args_hash = '' ): void;

	/**
	 * Sets the task's action ID.
	 *
	 * @since TBD
	 *
	 * @param int $action_id The task's action ID.
	 */
	public function set_action_id( int $action_id ): void;

	/**
	 * Sets the task's current try.
	 *
	 * @since TBD
	 *
	 * @param int $current_try The task's current try.
	 */
	public function set_current_try( int $current_try ): void;

	/**
	 * Gets the task's ID.
	 *
	 * @since TBD
	 *
	 * @return int The task's ID.
	 */
	public function get_id(): int;

	/**
	 * Gets the task's current try.
	 *
	 * @since TBD
	 *
	 * @return int The task's current try.
	 */
	public function get_current_try(): int;

	/**
	 * Gets the task's action ID.
	 *
	 * @since TBD
	 *
	 * @return int The task's action ID.
	 */
	public function get_action_id(): int;

	/**
	 * Gets the task's arguments hash.
	 *
	 * @since TBD
	 *
	 * @return string The task's arguments hash.
	 */
	public function get_args_hash(): string;

	/**
	 * Gets the task's arguments.
	 *
	 * @since TBD
	 *
	 * @return array The task's arguments.
	 */
	public function get_args(): array;

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
	 * @return int The task's retry delay.
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
	 * @return int The task's debounce delay.
	 */
	public function get_debounce_delay(): int;
}
