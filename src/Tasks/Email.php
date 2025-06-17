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
	 * Whether the email task is retryable.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	protected static bool $retryable = true;

	/**
	 * The maximum number of retries.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected static int $max_retries = 5;

	/**
	 * Processes the email task.
	 *
	 * @since TBD
	 *
	 * @throws PigeonTaskException If the email fails to send.
	 */
	public function process(): void {
		// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
		$result = wp_mail( ...$this->args );

		if ( ! $result ) {
			throw new PigeonTaskException( __( 'Failed to send email.', 'stellarwp-pigeon' ) );
		}

		if ( is_wp_error( $result ) ) {
			$message = sprintf(
				/* translators: %s: The error message. */
				__( 'Failed to send email with message: %s, code: %s and data: %s', 'stellarwp-pigeon' ),
				$result->get_error_message(),
				$result->get_error_code(),
				wp_json_encode( $result->get_error_data(), JSON_PRETTY_PRINT )
			);

			throw new PigeonTaskException( $message );
		}
	}

	/**
	 * Validates the email task's arguments.
	 *
	 * @since TBD
	 *
	 * @param array<mixed> $args The email task's arguments.
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	protected function validate_args( array $args ): void {
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
