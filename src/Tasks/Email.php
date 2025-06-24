<?php
/**
 * Pigeon's email task.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tasks;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;
use StellarWP\Pigeon\Exceptions\PigeonTaskException;
use InvalidArgumentException;

/**
 * Pigeon's email task.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tasks;
 */
class Email extends Task_Abstract {
	/**
	 * The maximum number of retries.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected static int $max_retries = 5;

	/**
	 * The email task's constructor.
	 *
	 * @since TBD
	 *
	 * @param string   $to_email    The email address to send the email to.
	 * @param string   $subject     The email subject.
	 * @param string   $body        The email body.
	 * @param string[] $headers     Optional. Additional headers.
	 * @param string[] $attachments Optional. Paths to files to attach.
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	public function __construct( string $to_email, string $subject, string $body, array $headers = [], array $attachments = [] ) {
		parent::__construct( $to_email, $subject, $body, $headers, $attachments );
	}

	/**
	 * Processes the email task.
	 *
	 * @since TBD
	 *
	 * @throws PigeonTaskException If the email fails to send.
	 */
	public function process(): void {
		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
		$result = wp_mail( ...$this->get_args() );

		if ( ! $result ) {
			throw new PigeonTaskException( __( 'Failed to send email.', 'stellarwp-pigeon' ) );
		}
	}

	/**
	 * Validates the email task's arguments.
	 *
	 * @since TBD
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	protected function validate_args(): void {
		$args = $this->get_args();
		if ( count( $args ) < 3 ) {
			throw new InvalidArgumentException( __( 'Email task requires at least 3 arguments.', 'stellarwp-pigeon' ) );
		}

		if ( ! is_email( $args[0] ) ) {
			throw new InvalidArgumentException( __( 'Email address is invalid.', 'stellarwp-pigeon' ) );
		}

		if ( ! is_string( $args[1] ) ) {
			throw new InvalidArgumentException( __( 'Email subject must be a string.', 'stellarwp-pigeon' ) );
		}

		if ( ! is_string( $args[2] ) ) {
			throw new InvalidArgumentException( __( 'Email body must be a string.', 'stellarwp-pigeon' ) );
		}
	}
}
