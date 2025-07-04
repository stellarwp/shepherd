<?php
/**
 * Pigeon Null Logger
 *
 * @package StellarWP\Pigeon\Loggers
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Loggers;

use StellarWP\Pigeon\Contracts\Logger as LoggerContract;
use Psr\Log\NullLogger;
use StellarWP\Pigeon\Log;

/**
 * Pigeon Null Logger
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Loggers
 */
class Null_Logger extends NullLogger implements LoggerContract {
	/**
	 * Retrieves the logs for a given task.
	 *
	 * @since TBD
	 *
	 * @param int $task_id The ID of the task.
	 *
	 * @return Log[] The logs for the task.
	 */
	public function retrieve_logs( int $task_id ): array {
		return [];
	}
}
