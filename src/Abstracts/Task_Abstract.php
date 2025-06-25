<?php
/**
 * Pigeon's task abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Abstracts;

use InvalidArgumentException;
use StellarWP\Pigeon\Contracts\Task;
use StellarWP\Pigeon\Config;
use JsonSerializable;
use StellarWP\Pigeon\Traits\Retryable;
use StellarWP\Pigeon\Traits\Debouncable;

/**
 * Pigeon's task abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Abstracts;
 */
abstract class Task_Abstract extends Task_Model_Abstract implements Task {
	use Retryable;
	use Debouncable;

	/**
	 * The task's group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const GROUP = 'pigeon_%s_queue_default';

	/**
	 * Whether the task is unique.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected const UNIQUE = false;

	/**
	 * The task's priority.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const PRIORITY = 10;

	/**
	 * The task's constructor.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$args The task's constructor arguments.
	 *
	 * @throws InvalidArgumentException If the task's constructor arguments are callable.
	 */
	public function __construct( ...$args ) {
		foreach ( $args as $arg ) {
			if ( is_callable( $arg ) ) {
				throw new InvalidArgumentException( 'Task constructor arguments must NOT be closures.' );
			}

			if ( is_object( $arg ) && ! $arg instanceof JsonSerializable ) {
				throw new InvalidArgumentException( 'Task constructor arguments should not be objects that are not JSON serializable.' );
			}
		}

		$this->set_args( $args );
		$this->validate_args();
		$this->set_class_hash();
		$this->set_data();
	}

	/**
	 * Gets the task's group.
	 *
	 * @since TBD
	 *
	 * @return string The task's group.
	 */
	public function get_group(): string {
		return sprintf( static::GROUP, Config::get_hook_prefix() );
	}

	/**
	 * Whether the task is unique.
	 *
	 * **IMPORTANT:** This should be set to true only when we want ONLY one
	 * scheduled task of this particular class at a time regardless of their arguments.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the task is unique.
	 */
	public function is_unique(): bool {
		return static::UNIQUE;
	}

	/**
	 * Gets the task's priority.
	 *
	 * @since TBD
	 *
	 * @return int The task's priority.
	 */
	public function get_priority(): int {
		return max( 0, min( 255, static::PRIORITY ) );
	}

	/**
	 * Validates the task's arguments.
	 *
	 * @since TBD
	 */
	protected function validate_args(): void {}
}
