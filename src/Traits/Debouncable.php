<?php
/**
 * Pigeon's debouncable trait.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Traits;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

trait Debouncable {
	/**
	 * Whether the task is debouncable.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected static bool $debouncable = true;

	/**
	 * Gets the debouncable status.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task is debouncable.
	 */
	public function is_debouncable(): bool {
		return static::$debouncable;
	}

	/**
	 * Gets the debounce delay.
	 *
	 * @since TBD
	 *
	 * @return int The debounce delay.
	 */
	public function get_debounce_delay(): int {
		return 0;
	}
}
