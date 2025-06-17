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
use StellarWP\Pigeon\Provider;
use JsonSerializable;

/**
 * Pigeon's task abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Abstracts;
 */
abstract class Task_Abstract implements Task {
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
	 * The task's constructor arguments.
	 *
	 * @since TBD
	 *
	 * @var array<mixed>
	 */
	protected array $args;

	/**
	 * The task's constructor.
	 *
	 * @since TBD
	 *
	 * @param mixed ...$args The task's constructor arguments.
	 *
	 * @throws InvalidArgumentException If the task's constructor arguments are callable.
	 */
	final public function __construct( ...$args ) {
		foreach ( $args as $arg ) {
			if ( is_callable( $arg ) ) {
				throw new InvalidArgumentException( 'Task constructor arguments must NOT be closures.' );
			}

			if ( is_object( $arg ) && ! $arg instanceof JsonSerializable ) {
				throw new InvalidArgumentException( 'Task constructor arguments should not be objects that are not JSON serializable.' );
			}
		}

		$this->args = $args;
	}

	/**
	 * Gets the task's arguments.
	 *
	 * @since TBD
	 *
	 * @return array The task's arguments.
	 */
	public function get_args(): array {
		return $this->args;
	}

	/**
	 * Gets the task's group.
	 *
	 * @since TBD
	 *
	 * @return string The task's group.
	 */
	public function get_group(): string {
		return sprintf( static::GROUP, Provider::get_hook_prefix() );
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
		return static::PRIORITY;
	}
}
