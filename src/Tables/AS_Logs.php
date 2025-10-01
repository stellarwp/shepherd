<?php
/**
 * The Action Scheduler logs table schema.
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Tables;
 */

namespace StellarWP\Shepherd\Tables;

use StellarWP\Shepherd\Abstracts\Table_Abstract;
use StellarWP\Shepherd\Log;
use StellarWP\Shepherd\Config;
use StellarWP\Schema\Collections\Column_Collection;
use StellarWP\Schema\Columns\ID;
use StellarWP\Schema\Columns\Referenced_ID;
use StellarWP\Schema\Columns\String_Column;
use StellarWP\Schema\Columns\Datetime_Column;
use StellarWP\Schema\Tables\Table_Schema;

/**
 * Action Scheduler logs table schema.
 *
 * This is used only as an interface and should not be registered as a table for schema to handle.
 *
 * @since 0.0.1
 * @since 0.0.8 Updated to be compatible with the updated contract.
 *
 * @package StellarWP\Shepherd\Tables;
 */
class AS_Logs extends Table_Abstract {
	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static $base_table_name = 'actionscheduler_logs';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static $uid_column = 'log_id';

	/**
	 * The version number for this schema definition.
	 *
	 * @since 0.0.8
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.1';

	/**
	 * Gets the schema history for the table.
	 *
	 * @since 0.0.8
	 *
	 * @return array<string, callable> The schema history for the table.
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name( true );

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'log_id' );
				$columns[] = new Referenced_ID( 'action_id' );
				$columns[] = new String_Column( 'message' );
				$columns[] = ( new Datetime_Column( 'log_date_gmt' ) )->set_nullable( true );
				$columns[] = ( new Datetime_Column( 'log_date_local' ) )->set_nullable( true );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * Gets the logs by task ID.
	 *
	 * @since 0.0.1
	 * @since 0.0.8 Updated to use the new get_all_by method.
	 *
	 * @param int $task_id The task ID.
	 *
	 * @return Log[] The logs for the task.
	 */
	public static function get_by_task_id( int $task_id ): array {
		return self::get_all_by( 'message', 'shepherd_' . Config::get_hook_prefix() . '||' . $task_id . '||%', 'LIKE', 1000 );
	}

	/**
	 * Gets a log from an array.
	 *
	 * @since 0.0.1
	 *
	 * @param array<string, mixed> $model_array The model array.
	 *
	 * @return Log The log.
	 */
	public static function transform_from_array( array $model_array ): Log {
		$log = new Log();
		$log->set_id( $model_array['log_id'] );
		$log->set_action_id( $model_array['action_id'] );
		$log->set_date( $model_array['log_date_gmt'] );

		$message = explode( '||', $model_array['message'] );
		$log->set_task_id( (int) ( $message[1] ?? 0 ) );
		$log->set_type( $message[2] ?? '' );
		$log->set_level( $message[3] ?? '' );
		$log->set_entry( $message[4] ?? '' );

		return $log;
	}
}
