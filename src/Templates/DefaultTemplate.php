<?php

namespace StellarWP\Pigeon\Templates;

final class DefaultTemplate implements TemplateInterface {

	protected $post_type_name = 'pigeon_templates';

	public function register() {
		$args = [
			'public' => false,
			'hierarchical' => false,
			'has_archive' => false,
			'rewrite' => false,
		];
		\register_post_type( $this->post_type_name, $args );
	}
}