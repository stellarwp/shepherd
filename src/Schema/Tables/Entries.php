<?php

namespace StellarWP\Pigeon\Schema\Tables;

use StellarWP\Pigeon\Config;
use StellarWP\Schema\Tables\Contracts;

class Entries extends Contracts\Table {

	const SCHEMA_VERSION = '1.0.0';

	const STATUS_DRAFT = 'draft';
	const STATUS_READY = 'ready';
	const STATUS_PROCESSING = 'processing';

	const STATUS_COMPLETE = 'complete';

	const COL_ENTRY_ID = [
		'name'    => 'entry_id',
		'type'    => 'bigint',
		'content' => 'NOT NULL',
		'extra'   => 'AUTO_INCREMENT PRIMARY KEY',
		'prepare' => '%d',
	];

	const COL_TEMPLATE_ID = [
		'name'    => 'template_id',
		'type'    => 'bigint',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%d',
	];

	const COL_CONTENT = [
		'name'    => 'content',
		'type'    => 'longtext',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_DELIVERY_MODULE = [
		'name'    => 'delivery_module',
		'type'    => 'varchar(200)',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_STATUS = [
		'name'    => 'status',
		'type'    => 'varchar(200)',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
		'accept'  => [ self::STATUS_COMPLETE, self::STATUS_DRAFT, self::STATUS_READY, self::STATUS_PROCESSING ],
	];

	const COL_RECIPIENT = [
		'name'    => 'recipient',
		'type'    => 'longtext',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_PUBLIC_KEY = [
		'name'    => 'public_key',
		'type'    => 'varchar(200)',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_PRIVATE_KEY = [
		'name'    => 'private_key',
		'type'    => 'varchar(200)',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_RETRIES = [
		'name'    => 'retries',
		'type'    => 'bigint',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%d',
	];

	const COL_CREATED_AT = [
		'name'    => 'created_at',
		'type'    => 'timestamp DEFAULT CURRENT_TIMESTAMP()',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_UPDATED_AT = [
		'name'    => 'updated_at',
		'type'    => 'timestamp DEFAULT CURRENT_TIMESTAMP()',
		'content' => 'NOT NULL ON UPDATE CURRENT_TIMESTAMP()',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COL_COMPLETED_AT = [
		'name'    => 'completed_at',
		'type'    => 'timestamp DEFAULT \'0000-00-00 00:00:00\'',
		'content' => 'NOT NULL',
		'extra'   => '',
		'prepare' => '%s',
	];

	const COLUMNS = [
		self::COL_ENTRY_ID,
		self::COL_TEMPLATE_ID,
		self::COL_CONTENT,
		self::COL_DELIVERY_MODULE,
		self::COL_STATUS,
		self::COL_RECIPIENT,
		self::COL_PUBLIC_KEY,
		self::COL_PRIVATE_KEY,
		self::COL_RETRIES,
		self::COL_CREATED_AT,
		self::COL_UPDATED_AT,
		self::COL_COMPLETED_AT,
	];

	protected static $base_table_name = 'pigeon_entries';

	protected static $uid_column = 'entry_id';

	protected static $schema_slug = 'pigeon_entries';

	protected function get_definition() {
		global $wpdb;
		$table_name      = static::base_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE {$table_name} (
			    {$this->build_ddl()}
		) {$charset_collate};
		";
	}

	protected function build_ddl() {
		foreach ( static::COLUMNS as $column ) {
			$ddl[] = "`{$column['name']}` {$column['type']} {$column['content']} {$column['extra']}";
		}

		return implode( ",\n", $ddl );
	}

	public static function base_table_name() {
		return rtrim( Config::get_hook_prefix(), '_' ) . '_' . static::$base_table_name;
	}

	public static function column_formats() {
		return [
			'entry_id'        => '%d',
			'template_id'     => '%d',
			'content'         => '%s',
			'delivery_module' => '%s',
			'status'          => '%s',
			'recipient'       => '%s',
			'public_key'      => '%s',
			'private_key'     => '%s',
			'retries'         => '%d',
			'completed_at' => '%s',
		];
	}
}