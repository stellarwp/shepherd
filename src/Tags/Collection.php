<?php

namespace StellarWP\Pigeon\Tags;

use StellarWP\Pigeon\Models\Entry;

class Collection {

	/**
	 * @var Tag[] $data;
	 */
	protected $data;

	public function add( Tag $tag ) {
		$this->data[ $tag->tag_name ] = $tag;
	}

	public function get_all() {
		$registered_tags = (array) apply_filters( 'stellarwp_pigeon_register_tag', [] );

		foreach ( $registered_tags as $tag ) {
			$this->add( $tag );
		}

		return (array) $this->data;
	}

	public function entry( Entry $entry ) {
		array_map(
			function ( Tag $tag ) use ( $entry ) {
				$tag->compose( $entry );
			},
			$this->get_all() 
		);
	}
}
