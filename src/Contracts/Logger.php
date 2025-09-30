<?php
/**
 * The Shepherd logger contract.
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Contracts
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Contracts;

use Psr\Log\LoggerInterface;
use StellarWP\Shepherd\Log;

/**
 * The Shepherd logger contract.
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Contracts
 */
interface Logger extends LoggerInterface {
	/**
	 * Retrieves the logs for a given task.
	 *
	 * @since 0.0.1
	 *
	 * @param int $task_id The ID of the task.
	 * @return Log[] The logs for the task.
	 */
	public function retrieve_logs( int $task_id ): array;

	/**
	 * Indicates if the logger uses its own table.
	 *
	 * @since 0.0.8
	 *
	 * @return bool
	 */
	public function uses_own_table(): bool;

	/**
	 * Indicates if the logger uses the Action Scheduler table.
	 *
	 * @since 0.0.8
	 *
	 * @return bool
	 */
	public function uses_as_table(): bool;
}
