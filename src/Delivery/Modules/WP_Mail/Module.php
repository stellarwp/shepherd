<?php

namespace StellarWP\Pigeon\Delivery\Modules\WP_Mail;

use StellarWP\Pigeon\Delivery\Modules\Module_Interface;
use StellarWP\Pigeon\Entry\Entry_Interface;
use StellarWP\Pigeon\Templates\Template_Interface;

class Module implements Module_Interface {


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