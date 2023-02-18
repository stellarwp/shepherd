<?php

namespace StellarWP\Pigeon\Delivery;

use PhpParser\Node\Expr\AssignOp\Mod;
use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Delivery\Modules\Module_Interface;
use StellarWP\Pigeon\Models\Entry;

class Envelope {

	public $available_modules;

	public $entry;

	/**
	 * @var Module_Interface;
	 */
	public $entry_module;

	public function __construct( Entry $entry ) {
		$this->entry = $entry;
		$this->set_available_modules();
		$this->set_entry_module();
	}

	public function set_available_modules() {
		$this->available_modules = [
			Mail::class,
		]; // The only module currently available is Mail.
	}

	public function get_available_modules() {
		return apply_filters( 'stellarwp_pigeon_available_modules', $this->available_modules );
	}

	public function get_entry_module() {
		return $this->entry_module;
	}

	public function set_entry_module() {

		if ( ! in_array( $this->entry->type, $this->get_available_modules(), true ) ) {
			return;
		}

		if ( ! in_array( Module_Interface::class, class_implements( $this->entry->type ), true ) ) {
			return;
		}

		$this->entry_module = $this->entry->type::init();
	}
}