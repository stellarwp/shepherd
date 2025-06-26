<?php
/**
 * Pigeon's functions.
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use RuntimeException;

/**
 * Get the Pigeon's Regulator instance.
 *
 * @since TBD
 *
 * @return Regulator The Pigeon's regulator.
 *
 * @throws RuntimeException If Pigeon is not registered.
 */
function pigeon(): Regulator {
	if ( ! Provider::is_registered() ) {
		throw new RuntimeException( 'Pigeon is not registered.' );
	}

	static $pigeon = null;

	if ( null !== $pigeon ) {
		return $pigeon;
	}

	$pigeon = Provider::get_container()->get( Regulator::class );

	return $pigeon;
}
