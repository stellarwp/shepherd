<?php
/**
 * The Task logs table schema.
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Tables;
 */

namespace StellarWP\Shepherd\Tables;

use StellarWP\Schema\Tables\Contracts\Table;
use StellarWP\Shepherd\Log;
use StellarWP\DB\DB;
use DateTime;
use StellarWP\Schema\Columns\Created_At;
use StellarWP\Schema\Columns\ID;
use StellarWP\Schema\Columns\Referenced_ID;
use StellarWP\Schema\Columns\String_Column;
use StellarWP\Schema\Columns\Text_Column;
use StellarWP\Schema\Columns\Column_Types;
use StellarWP\Schema\Tables\Table_Schema;
use StellarWP\Schema\Collections\Column_Collection;

/**
 * Task logs table schema.
 *
 * @since 0.0.1
 * @since 0.0.8 Updated to extend Table instead from the schema library.
 *
 * @package StellarWP\Shepherd\Tables;
 */
class Task_Logs extends Table {
	/**
	 * The schema version.
	 *
	 * @since 0.0.1
	 * @since 0.0.3 Updated to 0.0.3.
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.3';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static $base_table_name = 'shepherd_%s_task_logs';

	/**
	 * The table group.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static $group = 'stellarwp_shepherd';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static $schema_slug = 'stellarwp-shepherd-%s-task-logs';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	protected static $uid_column = 'id';

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
				$columns[] = new ID( 'id' );
				$columns[] = new Referenced_ID( 'task_id' );
				$columns[] = new Referenced_ID( 'action_id' );
				$columns[] = new Created_At( 'date' );
				$columns[] = ( new String_Column( 'level' ) )->set_length( 191 )->set_is_index( true );
				$columns[] = ( new String_Column( 'type' ) )->set_length( 191 )->set_is_index( true );
				$columns[] = ( new Text_Column( 'entry' ) )->set_type( Column_Types::LONGTEXT );

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
	 * @return Log[] The logs for the task.
	 */
	public static function get_by_task_id( int $task_id ): array {
		return self::get_all_by( 'task_id', $task_id, '=', 1000, 'date ASC' );
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
		$log->set_id( $model_array['id'] );
		$log->set_task_id( $model_array['task_id'] );
		$log->set_action_id( $model_array['action_id'] );
		$log->set_date( DateTime::createFromFormat( 'Y-m-d H:i:s', $model_array['date'] ) );
		$log->set_level( $model_array['level'] );
		$log->set_type( $model_array['type'] );
		$log->set_entry( $model_array['entry'] );

		return $log;
	}
}
