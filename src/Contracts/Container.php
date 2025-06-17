<?php
/**
 * Pigeon's container contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

use StellarWP\ContainerContract\ContainerInterface;

use lucatume\DI52\Container as DI52_Container;

/**
 * Pigeon's container contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts
 */
class Container extends DI52_Container implements ContainerInterface {}
