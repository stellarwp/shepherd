<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Entry_Interface;
use StellarWP\Pigeon\Templates\Template_Interface;

class Mail implements Module_Interface {


	public function deliver( Entry_Interface $entry ) {
		$contents = $entry->get_contents();
		$recipients = $entry->get_recipients();
		$template = $entry->get_template();


	}

	public function envelope( Template_Interface $template ) {
		// TODO: Implement envelope() method.
	}

	public function send( array $envelopes ) {
		// TODO: Implement send() method.


		\wp_mail();
	}
}