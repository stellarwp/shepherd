<?php
/**
 * Shepherd's container for tests.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Tests
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tests;

use StellarWP\ContainerContract\ContainerInterface;

use lucatume\DI52\Container as DI52_Container;

/**
 * Shepherd's container for tests.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Tests
 */
class Container extends DI52_Container implements ContainerInterface {}
