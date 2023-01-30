<?php

namespace StellarWP\Pigeon\Schema\Tables;

use StellarWP\Schema\Tables\Contracts;

class EntriesMeta extends Contracts\Table {

	const SCHEMA_VERSION = '1.0.0';

	protected static $base_table_name = 'pigeon_entries_meta';

	protected function get_definition() {
		global $wpdb;
		$table_name = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `{$table_name}` (
				meta_id         bigint                                  NOT NULL PRIMARY KEY,
				entry_id        bigint                                  NOT NULL COMMENT 'entry related to this meta',
				meta_key        varchar(200)                            NOT NULL COMMENT 'slug identifying this meta',
				meta_type       varchar(200)                            NOT NULL COMMENT 'type of the meta value',
				meta_value      varchar(200)                            NOT NULL,
				created_at      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL,
				updated_at      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL ON UPDATE CURRENT_TIMESTAMP(),
		) {$charset_collate};
		";

	}
}