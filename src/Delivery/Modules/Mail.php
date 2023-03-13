<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Base;
use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;

class Mail implements Module_Interface {

	public static $instance;

	public $scheduled = true;

	/**
	 * public static function init() :Mail {
	 * if ( static::$instance instanceof Mail ) {
	 * return static::$instance;
	 * }
	 *
	 * static::$instance = new Mail();
	 * return static::$instance;
	 * }
	 **/

	public static function send( Entry $entry ) {
		$to          = $entry->get( 'recipient' );
		$subject     = $entry->get( 'subject' );
		$message     = $entry->get( 'content' );
		$headers     = $entry->get( 'headers' );
		$attachments = $entry->get( 'attachments' );

		if ( empty( $attachments ) ) {
			$attachments = [];
		}

		$headers['X-Pigeon-Module'] = Mail::class;

		$success = wp_mail( $to, $subject, $message, $headers, $attachments );

		if ( $success ) {
			$entry->set_data( [ 'status' => 'complete', 'completed_at' => gmdate('c') ] );
			return;
		}

		$retries = $entry->get('retries');
		$entry->set_data( [ 'retries' => $retries + 1 ] );
	}
}



