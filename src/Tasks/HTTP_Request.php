<?php
/**
 * Pigeon's HTTP request task.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tasks;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tasks;

use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Abstracts\Task_Abstract;
use StellarWP\Pigeon\Exceptions\PigeonTaskException;
use StellarWP\Pigeon\Exceptions\PigeonTaskFailWithoutRetryException;
use InvalidArgumentException;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

/**
 * Pigeon's HTTP request task.
 *
 * This task makes HTTP requests using WordPress's wp_remote_request() function
 * with built-in retry logic for failed requests.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tasks;
 */
class HTTP_Request extends Task_Abstract {
	/**
	 * Valid HTTP methods.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const VALID_METHODS = [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS' ];

	/**
	 * Default request timeout in seconds.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	private const DEFAULT_ARGS = [
		'timeout'            => 3,
		'reject_unsafe_urls' => true, // Pass the URL(s) through the `wp_http_validate_url()` function.
		'compress'           => true, // Always compress the request.
		'decompress'         => true,
		'redirection'        => 5, // Follow up to 5 redirects.
	];

	/**
	 * The HTTP request task's constructor.
	 *
	 * @since TBD
	 *
	 * @param string $url     The URL to send the request to.
	 * @param array  $args    Optional. Request arguments (headers, body, timeout, etc.).
	 * @param string $method  Optional. HTTP method (GET, POST, etc.). Default 'GET'.
	 *
	 * @throws InvalidArgumentException If the HTTP request arguments are invalid.
	 */
	public function __construct( string $url, array $args = [], string $method = 'GET' ) {
		parent::__construct( $url, $args, strtoupper( $method ) );
	}

	/**
	 * Processes the HTTP request task.
	 *
	 * @since TBD
	 *
	 * @throws PigeonTaskException                 If the HTTP request fails but should be retried.
	 * @throws PigeonTaskFailWithoutRetryException If the HTTP request fails without retry.
	 */
	public function process(): void {
		$url          = $this->get_url();
		$method       = $this->get_method();
		$request_args = array_merge( self::DEFAULT_ARGS, $this->get_request_args() );

		// Set the HTTP method.
		$request_args['method'] = $method;

		if ( ! ( isset( $request_args['headers'] ) && is_array( $request_args['headers'] ) ) ) {
			$request_args['headers'] = [];
		}

		$request_args['headers'] = array_merge( $request_args['headers'], $this->get_auth_headers() );

		$request_args['headers']['X-Pigeon-Task-ID'] = $this->get_id();

		// Make the HTTP request.
		$response = wp_remote_request( $url, $request_args );

		// Check for WP_Error.
		if ( ! is_array( $response ) || is_wp_error( $response ) ) {
			throw new PigeonTaskFailWithoutRetryException(
				sprintf(
					/* translators: %1$s: HTTP method, %2$s: URL, %3$s: Error message */
					__( 'HTTP %1$s request to %2$s failed with code: `%3$s` and message: `%4$s`', 'stellarwp-pigeon' ),
					$method,
					$url,
					is_wp_error( $response ) ? $response->get_error_code() : __( 'unknown', 'stellarwp-pigeon' ),
					is_wp_error( $response ) ? $response->get_error_message() : __( 'unknown', 'stellarwp-pigeon' )
				)
			);
		}

		// Get response code.
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		// Check for HTTP error status codes (4xx, 5xx).
		if ( $response_code >= 400 && $response_code < 500 ) {
			throw new PigeonTaskFailWithoutRetryException(
				sprintf(
					/* translators: %1$s: HTTP method, %2$s: URL, %3$d: Response code, %4$s: Response message */
					__( 'HTTP %1$s request to %2$s returned error %3$d: `%4$s`', 'stellarwp-pigeon' ),
					$method,
					$url,
					$response_code,
					$response_message
				)
			);
		}

		if ( $response_code < 200 || $response_code >= 300 ) {
			throw new PigeonTaskException(
				sprintf(
					/* translators: %1$s: HTTP method, %2$s: URL, %3$d: Response code, %4$s: Response message */
					__( 'HTTP %1$s request to %2$s returned error %3$d: `%4$s`', 'stellarwp-pigeon' ),
					$method,
					$url,
					$response_code,
					$response_message
				)
			);
		}

		/**
		 * Fires when the HTTP request task is processed successfully.
		 *
		 * @since TBD
		 *
		 * @param HTTP_Request $task     The HTTP request task that was processed.
		 * @param array        $response The wp_remote_request response array.
		 */
		do_action( 'pigeon_' . Config::get_hook_prefix() . '_http_request_processed', $this, $response );
	}

	/**
	 * Validates the HTTP request task's arguments.
	 *
	 * @since TBD
	 *
	 * @throws InvalidArgumentException If the HTTP request arguments are invalid.
	 */
	protected function validate_args(): void {
		$args = $this->get_args();

		if ( count( $args ) < 1 ) {
			throw new InvalidArgumentException( __( 'HTTP request task requires at least a URL.', 'stellarwp-pigeon' ) );
		}

		// Validate URL.
		$url = $this->get_url();
		if ( ! ( is_string( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) ) {
			throw new InvalidArgumentException( __( 'URL is not valid.', 'stellarwp-pigeon' ) );
		}

		// Validate request arguments.
		if ( isset( $args[1] ) && ! is_array( $args[1] ) ) {
			throw new InvalidArgumentException( __( 'Request arguments must be an array.', 'stellarwp-pigeon' ) );
		}

		// Validate HTTP method.
		if ( isset( $args[2] ) && ! in_array( strtoupper( $args[2] ), self::VALID_METHODS, true ) ) {
			throw new InvalidArgumentException(
				sprintf(
					/* translators: %s: Valid HTTP methods */
					__( 'HTTP method must be one of: %s', 'stellarwp-pigeon' ),
					implode( ', ', self::VALID_METHODS )
				)
			);
		}
	}

	/**
	 * Gets the HTTP request task's hook prefix.
	 *
	 * @since TBD
	 *
	 * @return string The HTTP request task's hook prefix.
	 */
	public function get_task_prefix(): string {
		return 'pigeon_http_';
	}

	/**
	 * Gets the maximum number of retries.
	 *
	 * Network requests can be flaky, so allow retries.
	 *
	 * @since TBD
	 *
	 * @return int The maximum number of retries.
	 */
	public function get_max_retries(): int {
		return 10;
	}

	/**
	 * Gets the request URL.
	 *
	 * @since TBD
	 *
	 * @return string The request URL.
	 */
	public function get_url(): string {
		return $this->get_args()[0];
	}

	/**
	 * Gets the HTTP method.
	 *
	 * @since TBD
	 *
	 * @return string The HTTP method.
	 */
	public function get_method(): string {
		return $this->get_args()[2] ?? 'GET';
	}

	/**
	 * Gets the request arguments.
	 *
	 * @since TBD
	 *
	 * @return array The request arguments.
	 */
	public function get_request_args(): array {
		return $this->get_args()[1] ?? [];
	}

	/**
	 * Gets the authentication headers.
	 *
	 * Offers an alternative of having to store the auth credentials in the database.
	 *
	 * @since TBD
	 *
	 * @return array The authentication headers.
	 */
	public function get_auth_headers(): array {
		return [];
	}
}
