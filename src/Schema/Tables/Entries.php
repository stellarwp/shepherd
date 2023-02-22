<?php

namespace StellarWP\Pigeon\Schema\Tables;

use StellarWP\Pigeon\Config;
use StellarWP\Schema\Tables\Contracts;

class Entries extends Contracts\Table {

	const SCHEMA_VERSION = '1.0.0';

	protected static $base_table_name = 'pigeon_entries';

	protected static $uid_column = 'entry_id';

	protected static $schema_slug = 'pigeon_entries';

	protected function get_definition() {
		global $wpdb;
		$table_name      = static::base_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE {$table_name} (
				`entry_id`        bigint                                  NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`template_id`     bigint                                  NOT NULL,
				`content`         longtext                                NOT NULL,
				`delivery_module` varchar(200)                            NOT NULL,
				`status`          varchar(200)                            NOT NULL,
				`recipient`       longtext                                	      ,
				`public_key`      varchar(200)                            NOT NULL,
				`private_key`     varchar(200)                            NOT NULL,
				`retries`         bigint                                  NOT NULL,
				`created_at`      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL,
				`updated_at`      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL ON UPDATE CURRENT_TIMESTAMP(),
				`completed_at`    timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL
		) {$charset_collate};
		";
	}

	public static function base_table_name() {
		return rtrim( Config::get_hook_prefix(), '_' ) . '_' . static::$base_table_name;
	}

	public static function column_formats() {
		return [
			'entry_id' => '%d',
			'template_id' => '%d',
			'content' => '%s',
			'delivery_module' => '%s',
			'status' => '%s',
			'recipient' => '%s',
			'public_key' => '%s',
			'private_key' => '%s',
			'retries' => '%d',
			];
	}
}