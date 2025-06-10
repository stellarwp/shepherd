<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Tags\Collection;
use StellarWP\Pigeon\Tags\Default_Tags;

class Meta implements Model_Interface {

	/**
	 * @var Collection
	 */
	protected $tags;

	protected $keys;

	public function __construct() {
	}

	public function set_data(): Meta {
		$this->set_tags();
		return $this;
	}

	public function set_tags() {
		$tag_collection = Default_Tags::get();
		$this->tags     = $tag_collection->get_all();
		$this->set_key( 'tags', $this->tags );
	}

	public function set_key( $key, $value ) {
		$this->keys[ $key ] = $value;
	}

	public function validate_dataset(): bool {
		// TODO: Implement validate_dataset() method.
	}
}
