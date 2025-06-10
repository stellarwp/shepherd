<?php

namespace StellarWP\Pigeon\Delivery;

use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Delivery\Modules\Module_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Templates\Default_Template;

class Envelope {

	const MODULE_ACTIVE_SIGNATURE = 'X-Pigeon-Module';

	const MODULE_PROCESS_SIGNATURE = 'X-Pigeon-Process';

	public $available_modules;

	protected $entry;

	/**
	 * @var Module_Interface;
	 */
	public $entry_module;

	public function __construct( Entry $entry ) {
		$this->set_entry( $entry );
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

	public function set_entry( Entry $entry ) {
		$this->entry = $entry;
	}

	public function get_entry(): Entry {
		return $this->entry;
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

	public function package( $template_name, $modules = [ 'Mail' ], ...$args ) {
		$template = new Default_Template( $template_name );
		$template->set_args( $args );

		if ( $template->validate() ) {
			return '';
		}

		$entry = Entry::instance();
		$entry->set_data( $args );
		$template->set_entry( $entry );

		return $template->render()->get_rendered_content();
	}

	/**
	 * Package a message to be sent. Uses the same signature as wp_mail but routes
	 * the messages through Pigeon before delivering to the modules to send.
	 *
	 * @see wp_mail at wp-includes/pluggable.php
	 *
	 * @param string|string[] $to          Array or comma-separated list of email addresses to send message.
	 * @param string          $subject     Email subject.
	 * @param string          $message     Message contents.
	 * @param string|string[] $headers     Optional. Additional headers.
	 * @param string|string[] $attachments Optional. Paths to files to attach.
	 *
	 * @return bool Whether the entry was properly scheduled for sending
	 */
	public function create( ...$args ) {
		$this->get_entry()->set_data( $args )->schedule();
	}
}
