<?php
/**
 * The Action Scheduler actions table schema.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */

namespace StellarWP\Pigeon\Tables;

use StellarWP\Pigeon\Abstracts\Table_Abstract as Table;
use StellarWP\Pigeon\Contracts\Model;
use StellarWP\Pigeon\Log;

/**
 * Action Scheduler actions table schema.
 *
 * This is used only as an interface and should not be registered as a table for schema to handle.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */
class AS_Actions extends Table {
	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'actionscheduler_actions';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $uid_column = 'action_id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since TBD
	 *
	 * @return array<string, array<string, bool|int|string>>
	 */
	public static function get_columns(): array {
		return [
			static::$uid_column => [
				'type'           => self::COLUMN_TYPE_BIGINT,
				'php_type'       => self::PHP_TYPE_INT,
				'length'         => 20,
				'unsigned'       => true,
				'auto_increment' => true,
				'nullable'       => false,
			],
			'status'            => [
				'type'     => self::COLUMN_TYPE_VARCHAR,
				'php_type' => self::PHP_TYPE_STRING,
				'length'   => 20,
				'nullable' => false,
			],
		];
	}

	/**
	 * An array of all the columns that are searchable.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public static function get_searchable_columns(): array {
		return [ 'status' ];
	}

	/**
	 * Gets the model from an array.
	 *
	 * @since TBD
	 *
	 * @param array $model_array The model array.
	 *
	 * @return Model
	 */
	protected static function get_model_from_array( array $model_array ): Model {
		// fake method to satisfy the interface.
		return new Log();
	}
}
