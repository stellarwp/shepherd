<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Delivery\Modules\Module_Interface;

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

		} catch ( \Exception $exception ) {

		}

	}

	public function validate_dataset( $args ) :bool {

		switch ( $this->type ) {
			case Mail::class:
			default:
				return true;
				break;
		}
	}
}