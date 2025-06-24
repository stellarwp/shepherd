<?php
/**
 * Pigeon's container for tests.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tests
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests;

use StellarWP\ContainerContract\ContainerInterface;

use lucatume\DI52\Container as DI52_Container;

/**
 * Pigeon's container for tests.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Tests
 */
class Container extends DI52_Container implements ContainerInterface {}
