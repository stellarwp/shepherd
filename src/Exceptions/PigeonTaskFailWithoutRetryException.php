<?php
/**
 * Pigeon's task exception for failing without retry.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Exceptions;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Exceptions;

use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Pigeon's task exception for failing without retry.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Exceptions;
 */
class PigeonTaskFailWithoutRetryException extends Exception {}
