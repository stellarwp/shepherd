<?php

namespace StellarWP\Pigeon\Delivery;

use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Models\Entry;

class Envelope {

	public static function get_modules() {
		return [ Mail::class ]; // The only module currently available is Mail.
	}

	public static function dispatch( ...$args ) {
		$modules = static::get_modules();

		foreach( $modules as $module ) {
			$entry = new Entry( $module );
			$entry->set_data( $args );

			if( ! $entry->module_active() ) {
				continue;
			}

			$module = new $module($entry);
			$module->send();
		}
	}

}