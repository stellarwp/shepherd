<?php
/**
 * Abstract for Custom Tables.
 *
 * @since TDB
 *
 * @package StellarWP\Pigeon\Abstracts
 */

namespace StellarWP\Pigeon\Abstracts;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\DB\DB;

/**
 * Class Table_Abstract
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Abstracts
 */
abstract class Table_Abstract extends Table {
	public const PHP_TYPE_INT = 'int';
	public const PHP_TYPE_STRING = 'string';
	public const PHP_TYPE_BOOL = 'bool';
	public const PHP_TYPE_FLOAT = 'float';

	public const COLUMN_TYPE_BIGINT = 'bigint';
	public const COLUMN_TYPE_VARCHAR = 'varchar';
	public const COLUMN_TYPE_LONGTEXT = 'longtext';

	public const INDEXES = [];

	/**
	 * An array of all the columns in the table.
	 *
	 * @since TBD
	 *
	 * @var array<string, array<string, string>>
	 */
	abstract public static function get_columns(): array;

	/**
	 * An array of all the columns that are searchable.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	public static function get_searchable_columns(): array {
		return [];
	}

	/**
	 * Helper method to check and add an index to a table.
	 *
	 * @since TBD
	 *
	 * @param array  $results    The results array to track changes.
	 * @param string $index_name The name of the index.
	 * @param string $columns    The columns to index.
	 *
	 * @return array The updated results array.
	 */
	protected function check_and_add_index( array $results, string $index_name, string $columns ): array {
		$index_name = esc_sql( $index_name );

		// Add index only if it does not exist.
		if ( $this->has_index( $index_name ) ) {
			return $results;
		}

		$columns = esc_sql( $columns );

		DB::query(
			DB::prepare( "ALTER TABLE %i ADD INDEX `{$index_name}` ( {$columns} )", esc_sql( static::table_name( true ) ) )
		);

		return $results;
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since TBD
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();
		$uid_column      = self::uid_column();

		$columns = self::get_columns();

		$columns_definitions = [];
		foreach ( $columns as $column => $definition ) {
			$column_sql = "`{$column}` {$definition['type']}";

			if ( ! empty( $definition['length'] ) ) {
				$column_sql .= "({$definition['length']})";
			}

			if ( ! empty( $definition['unsigned'] ) ) {
				$column_sql .= ' UNSIGNED';
			}

			$column_sql .= ! empty( $definition['nullable'] ) ? ' NULL' : ' NOT NULL';

			if ( ! empty( $definition['auto_increment'] ) ) {
				$column_sql .= ' AUTO_INCREMENT';
			}

			if ( ! empty( $definition['default'] ) ) {
				$column_sql .= ' DEFAULT' . ( in_array( $definition['php_type'], [ self::PHP_TYPE_INT, self::PHP_TYPE_BOOL, self::PHP_TYPE_FLOAT ], true ) ? $definition['default'] : "'{$definition['default']}'" );
			}

			$columns_definitions[] = $column_sql;
		}

		$columns_sql = implode( ',' . PHP_EOL, $columns_definitions ) . ',' . PHP_EOL;

		return "
			CREATE TABLE `{$table_name}` (
				{$columns_sql}
				PRIMARY KEY (`{$uid_column}`)
			) {$charset_collate};
		";
	}

	/**
	 * Add indexes after table creation.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $results A map of results in the format
	 *                                      returned by the `dbDelta` function.
	 *
	 * @return array<string,string> A map of results in the format returned by
	 *                              the `dbDelta` function.
	 */
	protected function after_update( array $results ) {
		if ( empty( static::INDEXES ) || ! is_array( static::INDEXES ) ) {
			return $results;
		}

		foreach ( static::INDEXES as $index ) {
			$this->check_and_add_index( $results, $index['name'], $index['columns'] );
		}

		return $results;
	}
}
