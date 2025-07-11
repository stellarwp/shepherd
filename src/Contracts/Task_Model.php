<?php
/**
 * The Shepherd task model contract.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Contracts;
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Contracts;

/**
 * The Shepherd task model contract.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Contracts;
 */
interface Task_Model extends Model {
	/**
	 * Sets the task's arguments.
	 *
	 * @since TBD
	 *
	 * @param array $args The task's arguments.
	 */
	public function set_args( array $args ): void;

	/**
	 * Sets the task's data.
	 *
	 * @since TBD
	 */
	public function set_data(): void;

	/**
	 * Sets the task's arguments hash.
	 *
	 * @since TBD
	 */
	public function set_args_hash(): void;

	/**
	 * Sets the task's class hash.
	 *
	 * @since TBD
	 */
	public function set_class_hash(): void;

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
	 * Gets the task's class hash.
	 *
	 * @since TBD
	 *
	 * @return string The task's class hash.
	 */
	public function get_class_hash(): string;

	/**
	 * Gets the task's data.
	 *
	 * @since TBD
	 *
	 * @return string The task's data.
	 */
	public function get_data(): string;

	/**
	 * Gets the task's arguments.
	 *
	 * @since TBD
	 *
	 * @return array The task's arguments.
	 */
	public function get_args(): array;

	/**
	 * Gets the task's hook prefix.
	 *
	 * SHOULD BE a maximum of 15 characters.
	 *
	 * @since TBD
	 *
	 * @return string The task's prefix.
	 */
	public function get_task_prefix(): string;
}
