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
 * Get the Pigeon main controller.
 *
 * @since TBD
 *
 * @return Provider The Pigeon main controller.
 *
 * @throws RuntimeException If Pigeon is not registered.
 */
function pigeon(): Provider {
	if ( ! Provider::is_registered() ) {
		throw new RuntimeException( 'Pigeon is not registered.' );
	}

	static $pigeon = null;

	if ( null !== $pigeon ) {
		return $pigeon;
	}

	$pigeon = Provider::get_container()->get( Provider::class );

	return $pigeon;
}
