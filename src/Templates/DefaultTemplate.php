<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\EntryInterface;

final class DefaultTemplate implements TemplateInterface {

	protected $post_type_name = 'pigeon_templates';

	public function register() {
		$args = [
			'public' => true, // @TODO: change to false before deploying
			'hierarchical' => false,
			'has_archive' => false,
			'rewrite' => false,
		];
		\register_post_type( $this->post_type_name, $args );

		$this->create_default_template();
	}

	public function create_default_template() {
		// check if the default template exists, and create it
	}

	public function compose( EntryInterface $entry ) {
		// compose the template with entry data
	}

	public function render() {
		// render the default template
	}
}