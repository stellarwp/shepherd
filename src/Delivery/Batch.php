<?php

namespace StellarWP\Pigeon\Delivery;

use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Schema\Tables\Entries_Meta;

class Batch {

	const MAX_BATCH_SIZE = 50;

	const STATUS_TO_FETCH = 'ready';

	protected $type;

	protected $entries;

	public function __construct( $type = null ) {
		$this->type    = $type;
		$this->entries = $this->fetch();
	}

	public function fetch() {
		global $wpdb;
		$entries_table      = Entries::base_table_name();
		$entries_meta_table = Entries_Meta::base_table_name();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * from $entries_table as t1
         		LEFT JOIN $entries_meta_table as t2 ON t1.entry_id = t2.entry_id
				WHERE `status` = %s
				LIMIT %d;",
				static::STATUS_TO_FETCH,
				static::MAX_BATCH_SIZE,
			)
		);
	}

	public function dispatch() {

		if ( empty( $this->entries ) ) {
			return;
		}

		foreach ( $this->entries as $entry ) {
			$envelope = new Envelope( $entry );
			$module   = $envelope->get_entry_module();
			$module->send();
		}
	}

}