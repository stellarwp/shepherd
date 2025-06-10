<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Delivery\Envelope;
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

		$active    = Envelope::MODULE_ACTIVE_SIGNATURE;
		$class     = self::class;
		$headers[] = "{$active}: $class";

		$success = wp_mail( $to, $subject, $message, $headers, $attachments );

		if ( $success ) {
			$entry->set_data(
				[
					'status'       => 'complete',
					'completed_at' => gmdate( 'c' ),
				] 
			);

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
	 * @param array $args parameters
	 *
	 * @return null|bool. Returns null if the email was not intercepted. True if it was properly processed. False if
	 *                    not.
	 */
	public function intercept( $args ) {

		$array = array_filter( $args );
		$args  = array_pop( $array );

		if ( false !== in_array( $this->get_header_signature( Envelope::MODULE_ACTIVE_SIGNATURE ), $args['headers'], true ) ) {
			// Pigeon has already processed this
			return null;
		}

		$should_process = apply_filters( 'stellarwp_pigeon_process_message', false, $args );

		if ( ! $should_process && false === in_array( $this->get_header_signature( Envelope::MODULE_PROCESS_SIGNATURE ), $args['headers'], true ) ) {
			// Pigeon should not process this
			return null;
		}

		return static::envelope( $args );
	}

	protected function get_header_signature( $name ): string {

		switch ( $name ) {
			case Envelope::MODULE_PROCESS_SIGNATURE:
				$param = $name;
				$value = 'true';
				break;
			case Envelope::MODULE_ACTIVE_SIGNATURE:
				$param = $name;
				$value = __CLASS__;
				break;
			default:
				return '';
		}

		return "{$param}: $value";
	}
}
