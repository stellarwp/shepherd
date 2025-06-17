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
use StellarWP\Pigeon\Traits\Retryable;
use StellarWP\Pigeon\Traits\Debouncable;

/**
 * Pigeon's task abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Abstracts;
 */
abstract class Task_Abstract implements Task {
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
	 * The task's ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $id;

	/**
	 * The task's current try.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $current_try;

	/**
	 * The task's action ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $action_id;

	/**
	 * The task's arguments hash.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $args_hash;

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

		$this->validate_args( $args );

		$this->args = $args;
	}

	/**
	 * Sets the task's ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The task's ID.
	 */
	public function set_id( int $id ): void {
		$this->id = $id;
	}

	/**
	 * Sets the task's current try.
	 *
	 * @since TBD
	 *
	 * @param int $current_try The task's current try.
	 */
	public function set_current_try( int $current_try ): void {
		$this->current_try = $current_try;
	}

	/**
	 * Sets the task's action ID.
	 *
	 * @since TBD
	 *
	 * @param int $action_id The task's action ID.
	 */
	public function set_action_id( int $action_id ): void {
		$this->action_id = $action_id;
	}

	/**
	 * Sets the task's arguments hash.
	 *
	 * @since TBD
	 *
	 * @param string $args_hash The task's arguments hash.
	 *
	 * @throws InvalidArgumentException If the task arguments hash does not match the expected hash.
	 */
	public function set_args_hash( string $args_hash = '' ): void {
		if ( $args_hash && $args_hash !== md5( wp_json_encode( $this->args ) ) ) {
			throw new InvalidArgumentException( 'The task arguments hash does not match the expected hash.' );
		}

		$this->args_hash = $args_hash;
	}

	/**
	 * Validates the task's arguments.
	 *
	 * @since TBD
	 *
	 * @param array<mixed> $args The task's arguments.
	 *
	 * @throws InvalidArgumentException If the task's arguments are invalid.
	 */
	protected function validate_args( array $args ): void {}

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
		return max( 0, min( 255, static::PRIORITY ) );
	}
}
