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

use InvalidArgumentException;

/**
 * Pigeon's task contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */
interface Task {
	/**
	 * The task's constructor.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$args The task's constructor arguments.
	 *
	 * @throws InvalidArgumentException If the task's constructor arguments are callable.
	 */
	public function __construct( ...$args );

	/**
	 * Processes the task.
	 *
	 * @since TBD
	 */
	public function process(): void;

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
}
