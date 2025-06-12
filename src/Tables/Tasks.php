<?php
/**
 * The Tasks table schema.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */

namespace StellarWP\Pigeon\Tables;

use StellarWP\Pigeon\Abstracts\Table_Abstract as Table;

/**
 * Tasks table schema.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tables;
 */
class Tasks extends Table {
	/**
	 * The indexes for the table.
	 *
	 * @since TBD
	 *
	 * @var array<string, array<string, string>>
	 */
	public const INDEXES = [
		[
			'name'    => 'action_id',
			'columns' => 'action_id',
		],
		[
			'name'    => 'args_hash',
			'columns' => 'args_hash',
		],
	];

	/**
	 * The schema version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.1-dev';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'stellarwp_pigeon_%s_tasks';

	/**
	 * The table group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'stellarwp_pigeon';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'stellarwp-pigeon-%s-tasks';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $uid_column = 'id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since TBD
	 *
	 * @var array<string, array<string, string>>
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
			'action_id'         => [
				'type'     => self::COLUMN_TYPE_BIGINT,
				'php_type' => self::PHP_TYPE_INT,
				'length'   => 20,
				'unsigned' => true,
				'nullable' => false,
			],
			'args_hash'         => [
				'type'     => self::COLUMN_TYPE_VARCHAR,
				'php_type' => self::PHP_TYPE_STRING,
				'length'   => 128,
				'nullable' => false,
			],
			'args'              => [
				'type'     => self::COLUMN_TYPE_LONGTEXT,
				'php_type' => self::PHP_TYPE_STRING,
				'nullable' => true,
			],
		];
	}
}
