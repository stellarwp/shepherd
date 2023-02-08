<?php

namespace StellarWP\Pigeon\Schema\Tables;

use StellarWP\Schema\Tables\Contracts;
use StellarWP\Pigeon\Config\Config;

class Entries_Meta extends Contracts\Table {

	const SCHEMA_VERSION = '1.0.0';

	protected static $base_table_name = 'pigeon_entries_meta';

	protected static $uid_column = 'meta_id';

	protected static $schema_slug = 'pigeon_entries_meta';

	protected function get_definition() {
		global $wpdb;
		$table_name = self::base_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `{$table_name}` (
				`meta_id`         bigint                                  NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`entry_id`        bigint                                  NOT NULL,
				`meta_key`        varchar(200)                            NOT NULL,
				`meta_type`       varchar(200)                            NOT NULL,
				`meta_value`      varchar(200)                            NOT NULL,
				`created_at`      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL,
				`updated_at`      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL ON UPDATE CURRENT_TIMESTAMP()
		) {$charset_collate};
		";

	}

	public static function base_table_name() {
		return rtrim( Config::get_hook_prefix(), '_' ) . '_' . static::$base_table_name;
	}
}