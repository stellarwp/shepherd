<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Base;
use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Templates\Template_Interface;

class Mail implements Module_Interface {

	public static $instance;

	public static function init() {
		if ( static::$instance instanceof Mail ) {
			return static::$instance;
		}

		static::$instance = new Mail();
		return static::$instance;
	}

	public function deliver( Model_Interface $entry ) {
		$contents = $entry->get_contents();
		$recipients = $entry->get_recipients();
		$template = $entry->get_template();
	}

	public function send( array $envelopes ) {
		// TODO: Implement send() method.
	}

}