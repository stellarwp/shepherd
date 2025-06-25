<?php
/**
 * The Pigeon log model contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

use DateTimeInterface;

/**
 * The Pigeon log model contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts
 */
interface Log_Model extends Model {
	/**
	 * Sets the task ID.
	 *
	 * @since TBD
	 *
	 * @param int $task_id The task ID.
	 *
	 * @return void The method does not return any value.
	 */
	public function set_task_id( int $task_id ): void;

	/**
	 * Sets the date.
	 *
	 * @since TBD
	 *
	 * @param DateTimeInterface $date The date.
	 *
	 * @return void The method does not return any value.
	 */
	public function set_date( DateTimeInterface $date ): void;

	/**
	 * Sets the level.
	 *
	 * @since TBD
	 *
	 * @param string $level The level.
	 *
	 * @return void The method does not return any value.
	 */
	public function set_level( string $level ): void;

	/**
	 * Sets the type.
	 *
	 * @since TBD
	 *
	 * @param string $type The type.
	 *
	 * @return void The method does not return any value.
	 */
	public function set_type( string $type ): void;

	/**
	 * Sets the entry.
	 *
	 * @since TBD
	 *
	 * @param string $entry The entry.
	 *
	 * @return void The method does not return any value.
	 */
	public function set_entry( string $entry ): void;

	/**
	 * Gets the task ID.
	 *
	 * @since TBD
	 *
	 * @return int The task ID.
	 */
	public function get_task_id(): int;

	/**
	 * Gets the date.
	 *
	 * @since TBD
	 *
	 * @return DateTimeInterface The date.
	 */
	public function get_date(): DateTimeInterface;

	/**
	 * Gets the level.
	 *
	 * @since TBD
	 *
	 * @return string The level.
	 */
	public function get_level(): string;

	/**
	 * Gets the type.
	 *
	 * @since TBD
	 *
	 * @return string The type.
	 */
	public function get_type(): string;

	/**
	 * Gets the entry.
	 *
	 * @since TBD
	 *
	 * @return string The entry.
	 */
	public function get_entry(): string;
}
