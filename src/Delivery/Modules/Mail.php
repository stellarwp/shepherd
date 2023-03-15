<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Delivery\Envelope;
use StellarWP\Pigeon\Entry\Base;
use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Schema\Tables\Entries;

class Mail implements Module_Interface {

	public static $instance;

	public $scheduled = true;

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
			$entry->set_data( [ 'status' => 'complete', 'completed_at' => gmdate( 'c' ) ] );

			return;
		}

		$retries = $entry->get( 'retries' );
		$entry->set_data( [ 'retries' => $retries + 1 ] );
	}

	/**
	 * Intercept emails from wp_mail and check if Pigeon should process them.
	 *
	 * @param $args wp_mail parameters
	 *
	 * @return true for email scheduled, false for not scheduled
	 */
	public function intercept( $args ) {

		$array = array_filter( $args );
		$args  = array_pop( $array );

		if ( ! empty( $args['headers']['X-Pigeon-Module'] ) ) {
			// Pigeon has already processed this
			return null;
		}

		$should_process = apply_filters( 'stellarwp_pigeon_process_message', false, $args );

		if ( ! $should_process && empty( $args['headers']['X-Pigeon-Process'] ) ) {
			// Pigeon should not process this
			return null;
		}

		/**
		 * @var $envelope Envelope;
		 */
		$envelope = new Envelope( new Entry() );
		$envelope->create( $args );

		return (bool) $envelope->get_entry()->get( Entries::COL_STATUS['name'] );
	}
}



