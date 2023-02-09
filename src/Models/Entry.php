<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Delivery\Modules\Module_Interface;
use StellarWP\Pigeon\Schema\Tables\Entries;

class Entry implements Model_Interface {

	public $type;

	public $raw_data;


	public function __constructor( Module_Interface $module ) :void {
		$this->type = $module::class;
	}

	public function set_data( ...$args ) :void {
		$this->raw_data = $args;

		try {
			$this->validate_dataset();
			$this->compose()->save();
		} catch ( \Exception $exception ) {

		}
	}

	public function module_active() {
		return true;
	}

	public function validate_dataset() :bool {

		switch ( $this->type ) {
			case Mail::class:
			default:
				return true;
		}
	}

	public function compose() :Entry {
		$this->data = $this->raw_data;
		return $this;
	}

	public function save() :Entry {
		global $wpdb;
		$entry_table = Entries::base_table_name();
		$formats = Entries::column_formats();

		if ( false === $wpdb->insert( $entry_table, $this->data, $formats ) ) {
			throw new \Exception();
		}

		return $this;
	}
}