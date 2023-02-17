<?php

namespace StellarWP\Pigeon\Tags;

final class Default_Tags {

	public static function get() {
		return static::build( [
			'send_date'      => __( 'Send date', 'stellarwp_pigeon' ),
			'send_time'      => __( 'Send time', 'stellarwp_pigeon' ),
			'template_title' => __( 'Template Title', 'stellarwp_pigeon' ),
		] );
	}

	protected static function build( array $default_tags ) :Collection {
		$collection = new Collection();

		return array_map( function( $slug, $tag ) use ( $collection ) {
			return $collection->add( new Tag( $slug, $tag ) );
		}, $default_tags );
	}
}