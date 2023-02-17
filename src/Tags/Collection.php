<?php

namespace StellarWP\Pigeon\Tags;

class Collection {

	/**
	 * @var Tag[] $data;
	 */
	protected $data;

	public function add( Tag $tag ) {
		$this->data[ $tag->slug ] = $tag;
	}

	public function get_all() {
		$registered_tags = (array) apply_filters( 'stellarwp_pigeon_register_tag', [] );

		foreach ( $registered_tags as $tag ) {
			$this->add( $tag );
		}

		return $this;
	}
}