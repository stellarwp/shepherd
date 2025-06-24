<?php
/**
 * Pigeon's retryable trait.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Traits;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

trait Retryable {
	/**
	 * The maximum number of retries.
	 *
	 * If the task is retryable, and the maximum number of retries is reached, the task will be marked as failed.
	 *
	 * If specified 0 or less, the task will be retried indefinitely.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected static int $max_retries = 1;

	/**
	 * Gets the retryable status.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task is retryable.
	 */
	public function is_retryable(): bool {
		return static::$max_retries !== 1;
	}

	/**
	 * Checks if the task should be retried.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task should be retried.
	 */
	public function should_retry(): bool {
		if ( static::$max_retries <= 0 ) {
			return true;
		}

		return $this->current_try < static::$max_retries;
	}

	/**
	 * Gets the retry delay.
	 *
	 * @since TBD
	 *
	 * @return int The retry delay in seconds.
	 */
	public function get_retry_delay(): int {
		return 0;
	}
}
