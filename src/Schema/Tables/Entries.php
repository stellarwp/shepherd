<?php

namespace StellarWP\Pigeon\Schema\Tables;

use StellarWP\Schema\Tables\Contracts;

class Entries extends Contracts\Table {

	const SCHEMA_VERSION = '1.0.0';

	protected static $base_table_name = 'pigeon_entries';

	protected function get_definition() {
		global $wpdb;
		$table_name = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `{$table_name}` (
				entry_id        bigint                                  NOT NULL PRIMARY KEY,
				template_id     bigint                                  NOT NULL COMMENT 'wordpress post used to generate this entry',
				content         longtext                                NOT NULL COMMENT 'content sent in this entry',
				delivery_module varchar(200)                            NOT NULL COMMENT 'slug of the module used to send this entry',
				status          varchar(200)                            NOT NULL COMMENT 'entry status',
				to              longtext                                		 COMMENT 'recipient',
				created_at      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL,
				updated_at      timestamp DEFAULT CURRENT_TIMESTAMP()   NOT NULL ON UPDATE CURRENT_TIMESTAMP(),
				completed_at    timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL COMMENT 'when the completed status was reached',
				public_key      varchar(200)                            NOT NULL COMMENT 'unique hash, can be shared publicly',
				private_key     varchar(200)                            NOT NULL COMMENT 'unique hash, cannot be shared publicly'
		) {$charset_collate};
		";

	}
}