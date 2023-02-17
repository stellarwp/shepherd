<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Model_Interface;

final class Default_Template implements Template_Interface {

	protected $post_type_name = 'stellarwp_pigeon_templates';

	public function register() {
		$args = [
			'label'           => __( 'Pigeon Templates', 'stellarwp_pigeon' ),
			'labels'          => [
				'name'          => __( 'Pigeon Template', 'stellarwp_pigeon' ),
				'singular_name' => __( 'Pigeon Templates', 'stellarwp_pigeon' ),
			],
			'public' => true, // @TODO: change to false before deploying
			'supports' => ['title', 'editor'],
			'hierarchical' => true,
			'has_archive' => false,
			'rewrite' => false,
			'show_ui' => true,
		];
		\register_post_type( $this->post_type_name, $args );

		$this->create_default_template();
	}

	public function create_default_template() {
		// check if the default template exists, and create it
	}

	public function compose( Model_Interface $entry ) {
		// compose the template with entry data
	}

	public function render() {
		// render the default template
	}
}