<?php

namespace StellarWP\Pigeon\Delivery;

use StellarWP\Pigeon\Delivery\Modules\Module_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Schema\Tables\Entries_Meta;

class Batch {

	const MAX_BATCH_SIZE = 500;

	const STATUS_TO_FETCH = 'ready';

	protected $type;

	protected $entries;

	public function __construct( $type = null ) {
		$this->type = $type;
		$this->fetch();
		$this->set_processing();
	}

	public function get_entries() {
		return $this->entries;
	}

	public function set_entries( $entries ) {
		$this->entries = $entries;
	}

	public function fetch() {
		global $wpdb;
		$entries_table      = Entries::base_table_name();
		$entries_meta_table = Entries_Meta::base_table_name();

		$entries = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT entry_id from $entries_table as t1
				WHERE `status` = %s
				LIMIT %d;",
				static::STATUS_TO_FETCH,
				static::MAX_BATCH_SIZE,
			),
			ARRAY_A
		);

		$this->entries = wp_list_pluck( $entries, 'entry_id' );
	}

	public function set_processing() {
		global $wpdb;
		$entries_table = Entries::base_table_name();

		$wpdb->query(
			$wpdb->prepare( "
			UPDATE $entries_table
			SET status = %s
			WHERE entry_id IN ( %s )
			",
				Entries::STATUS_READY,
				implode( ',', $this->entries )
			)
		);
	}

	public function dispatch() {

		if ( empty( $this->entries ) ) {
			return;
		}

		foreach ( $this->entries as $entry ) {
			$entry_obj = new Entry();
			$entry_obj->load( $entry );
			/**
			 * @var Module_Interface
			 */
			$module = $entry_obj->get( 'delivery_module' );
			$module::send( $entry_obj );
		}
	}
}