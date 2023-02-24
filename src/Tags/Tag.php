<?php

namespace StellarWP\Pigeon\Tags;

abstract class Tag implements Tag_Interface{

	public function __construct( $slug, $args ) {
	}

	public function get_data( Entry $entry ) {
		// TODO: Implement get_data() method.
	}

	public function print() {
		// TODO: Implement print() method.
	}

	public function get_tag_name() {
		return "%%{$this->tag_name}%%"
	}
}