<?php
/**
 * The Pigeon log model abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use StellarWP\Pigeon\Contracts\Log_Model;
use StellarWP\Pigeon\Tables\Task_Logs as Task_Logs_Table;
use StellarWP\Pigeon\Provider;
use StellarWP\Pigeon\Abstracts\Model_Abstract;
use DateTimeInterface;
use StellarWP\Pigeon\Abstracts\Table_Abstract;
use Psr\Log\LogLevel;
use InvalidArgumentException;
use DateTime;

/**
 * The Pigeon log model abstract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */
class Log extends Model_Abstract implements Log_Model {
	/**
	 * The table interface for the log.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TABLE_INTERFACE = Task_Logs_Table::class;

	/**
	 * The valid log levels.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	public const VALID_LEVELS = [
		LogLevel::INFO,
		LogLevel::WARNING,
		LogLevel::ERROR,
		LogLevel::DEBUG,
		LogLevel::EMERGENCY,
		LogLevel::CRITICAL,
		LogLevel::ALERT,
		LogLevel::NOTICE,
	];

	/**
	 * The valid log types.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	public const VALID_TYPES = [
		'created',
		'started',
		'finished',
		'failed',
		'rescheduled',
		'cancelled',
		'retrying',
	];

	/**
	 * The task ID.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $task_id = 0;

	/**
	 * The date.
	 *
	 * @since TBD
	 *
	 * @var DateTimeInterface
	 */
	protected ?DateTimeInterface $date = null;

	/**
	 * The level.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $level;

	/**
	 * The type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * The entry.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $entry;

	/**
	 * Sets the task ID.
	 *
	 * @since TBD
	 *
	 * @param int $task_id The task ID.
	 */
	public function set_task_id( int $task_id ): void {
		$this->task_id = $task_id;
	}

	/**
	 * Sets the date.
	 *
	 * @since TBD
	 *
	 * @param DateTimeInterface $date The date.
	 */
	public function set_date( DateTimeInterface $date ): void {
		$this->date = $date;
	}

	/**
	 * Sets the level.
	 *
	 * @since TBD
	 *
	 * @param string $level The level.
	 *
	 * @throws InvalidArgumentException If the log level is invalid.
	 */
	public function set_level( string $level ): void {
		if ( ! in_array( $level, self::VALID_LEVELS, true ) ) {
			throw new InvalidArgumentException( 'Invalid log level.' );
		}

		$this->level = $level;
	}

	/**
	 * Sets the type.
	 *
	 * @since TBD
	 *
	 * @param string $type The type.
	 *
	 * @throws InvalidArgumentException If the log type is invalid.
	 */
	public function set_type( string $type ): void {
		if ( ! in_array( $type, self::VALID_TYPES, true ) ) {
			throw new InvalidArgumentException( 'Invalid log type.' );
		}

		$this->type = $type;
	}

	/**
	 * Sets the entry.
	 *
	 * @since TBD
	 *
	 * @param string $entry The entry.
	 */
	public function set_entry( string $entry ): void {
		$this->entry = trim( $entry );
	}

	/**
	 * Gets the task ID.
	 *
	 * @since TBD
	 *
	 * @return int The task ID.
	 */
	public function get_task_id(): int {
		return $this->task_id;
	}

	/**
	 * Gets the date.
	 *
	 * @since TBD
	 *
	 * @return DateTimeInterface The date.
	 */
	public function get_date(): DateTimeInterface {
		return $this->date ?? new DateTime();
	}

	/**
	 * Gets the level.
	 *
	 * @since TBD
	 *
	 * @return string The level.
	 */
	public function get_level(): string {
		return $this->level;
	}

	/**
	 * Gets the type.
	 *
	 * @since TBD
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Gets the entry.
	 *
	 * @since TBD
	 *
	 * @return string The entry.
	 */
	public function get_entry(): string {
		return $this->entry;
	}

	/**
	 * Gets the table interface for the log.
	 *
	 * @since TBD
	 *
	 * @return Table_Abstract The table interface.
	 */
	public function get_table_interface(): Table_Abstract {
		return Provider::get_container()->get( static::TABLE_INTERFACE );
	}
}
