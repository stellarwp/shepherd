<?php
/**
 * The Pigeon loggable trait.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Traits;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Traits;

use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Contracts\Logger;
use Psr\Log\LogLevel;
use StellarWP\Pigeon\Log;

/**
 * The Pigeon loggable trait.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Traits;
 */
trait Loggable {
	/**
	 * The logger.
	 *
	 * @since TBD
	 *
	 * @var Logger|null
	 */
	private ?Logger $logger = null;

	/**
	 * Gets the logger.
	 *
	 * @since TBD
	 *
	 * @return Logger The logger.
	 */
	private function get_logger(): Logger {
		if ( ! $this->logger ) {
			$this->logger = Config::get_container()->get( Logger::class );
		}

		return $this->logger;
	}

	/**
	 * Logs a message.
	 *
	 * @since TBD
	 *
	 * @param string $level   The log level.
	 * @param string $type    The log type.
	 * @param int    $task_id The task ID.
	 * @param string $message The message to log.
	 * @param array  $data    The data to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log( string $level, string $type, int $task_id, string $message, array $data = [] ): void {
		$data['task_id'] = $task_id;
		$data['type']    = $type;
		$this->get_logger()->log( $level, $message, $data );
	}

	/**
	 * Logs a created message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_created( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d created.', $task_id );
		$this->log( LogLevel::INFO, Log::TYPE_CREATED, $task_id, $message, $data );
	}

	/**
	 * Logs a starting message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_starting( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d starting.', $task_id );
		$this->log( LogLevel::INFO, Log::TYPE_STARTED, $task_id, $message, $data );
	}

	/**
	 * Logs a finished message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_finished( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d finished.', $task_id );
		$this->log( LogLevel::INFO, Log::TYPE_FINISHED, $task_id, $message, $data );
	}

	/**
	 * Logs a failed message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_failed( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d failed.', $task_id );
		$this->log( LogLevel::ERROR, Log::TYPE_FAILED, $task_id, $message, $data );
	}

	/**
	 * Logs a rescheduled message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_rescheduled( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d rescheduled.', $task_id );
		$this->log( LogLevel::NOTICE, Log::TYPE_RESCHEDULED, $task_id, $message, $data );
	}

	/**
	 * Logs a cancelled message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_cancelled( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d cancelled.', $task_id );
		$this->log( LogLevel::NOTICE, Log::TYPE_CANCELLED, $task_id, $message, $data );
	}

	/**
	 * Logs a retrying message.
	 *
	 * @since TBD
	 *
	 * @param int    $task_id The task ID.
	 * @param array  $data    The data to log.
	 * @param string $message The message to log.
	 *
	 * @return void The method does not return any value.
	 */
	public function log_retrying( int $task_id, array $data = [], string $message = '' ): void {
		$message = $message ?: sprintf( 'Task %d retrying.', $task_id );
		$this->log( LogLevel::INFO, Log::TYPE_RETRYING, $task_id, $message, $data );
	}
}
