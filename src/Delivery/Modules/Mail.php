<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Delivery\Envelope;
use StellarWP\Pigeon\Entry\Base;
use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Schema\Tables\Entries;

/**
 * E-mail Delivery Module
 *
 * @since TBD
 *
 * @package StellarWP/Pigeon
 */
class Mail implements Module_Interface {

	/**
	 * Get data from Entry and pass along to wp_mail
	 *
	 * @since TBD
	 *
	 * @param Entry $entry Entry object to send
	 */
	public static function send( Entry $entry ) {
		$to          = $entry->get( 'recipient' );
		$subject     = $entry->get( 'subject' );
		$message     = $entry->get( 'content' );
		$headers     = $entry->get( 'headers' );
		$attachments = $entry->get( 'attachments' );

		if ( empty( $attachments ) ) {
			$attachments = [];
		}

		$headers[ Envelope::MODULE_ACTIVE_SIGNATURE ] = Mail::class;

		$success = wp_mail( $to, $subject, $message, $headers, $attachments );

		if ( $success ) {
			$entry->set_data( [ 'status' => 'complete', 'completed_at' => gmdate( 'c' ) ] );
			return;
		}

		$retries = $entry->get( 'retries' );
		$entry->set_data( [ 'retries' => $retries + 1 ] );
	}

	public static function envelope( $args ) {
		/**
		 * @var $envelope Envelope;
		 */
		$envelope = new Envelope( new Entry() );
		$envelope->create( $args );

		return (bool) $envelope->get_entry()->get( Entries::COL_STATUS['name'] );
	}

	/**
	 * Intercept emails from wp_mail and check if Pigeon should process them.
	 *
	 * @since TBD
	 *
	 * @param $args wp_mail parameters
	 *
	 * @return null|bool. Returns null if the email was not intercepted. True if it was properly processed. False if not.
	 */
	public function intercept( $args ) {

		$array = array_filter( $args );
		$args  = array_pop( $array );

		if ( ! empty( $args['headers'][ Envelope::MODULE_ACTIVE_SIGNATURE ] ) ) {
			// Pigeon has already processed this
			return null;
		}

		$should_process = apply_filters( 'stellarwp_pigeon_process_message', false, $args );

		if ( ! $should_process && empty( $args['headers'][ Envelope::MODULE_PROCESS_SIGNATURE ] ) ) {
			// Pigeon should not process this
			return null;
		}

		return static::envelope( $args );
	}
}



