<?php
/**
 * The Pigeon logger contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

use Psr\Log\LoggerInterface;
use StellarWP\Pigeon\Log;

/**
 * The Pigeon logger contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts;
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
