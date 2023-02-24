<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Envelope;
use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Delivery\Modules\Module_Interface;
use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Tags\Collection;
use StellarWP\Pigeon\Templates\Default_Template;

class Entry implements Model_Interface {

	public $type;

	public $data;


	public function __construct() {
	}

	public function set_data( ...$args ) :Entry {
		$this->raw_data = $args;

		try {
			$this->validate_dataset();
			$this->compose()->save();
		} catch ( \Exception $exception ) {

		}

		return $this;
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
		$this->tag_collection = new Collection();
		$this->tag_collection->get_all();
		$this->template = new Default_Template( 'tickets/email' );

		$this->data = [
			'template_id' => $this->template->get_key('ID'),
			'content' => $this->template->render( $this->tag_collection )->rendered,
			'delivery_module' => 'mail',
			'status' => 'draft',
			'public_key' => 'pubkey',
			'private_key' => 'privkey',
			'retries' => 0,
		];

		return $this;
	}

	public function save() :Entry {
		global $wpdb;
		$entry_table = Entries::base_table_name();
		$formats = Entries::column_formats();

		if ( false === $wpdb->insert( $entry_table, $this->data ) ) {
			throw new \Exception();
		}

		return $this;
	}

	public function schedule()  :Entry {
		return $this;
	}
}