<?php
/**
 * Shepherd's task exception for failing without retry.
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Exceptions;
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Exceptions;

use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Shepherd's task exception for failing without retry.
 *
 * @since 0.0.1
 *
 * @package StellarWP\Shepherd\Exceptions;
 */
class ShepherdTaskFailWithoutRetryException extends Exception {}
