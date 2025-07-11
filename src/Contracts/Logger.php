<?php
/**
 * The Shepherd logger contract.
 *
 * @since TBD
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
 * @since TBD
 *
 * @package StellarWP\Shepherd\Contracts
 */
interface Logger extends LoggerInterface {
	/**
	 * Retrieves the logs for a given task.
	 *
	 * @since TBD
	 *
	 * @param int $task_id The ID of the task.
	 * @return Log[] The logs for the task.
	 */
	public function retrieve_logs( int $task_id ): array;
}
