<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use const Patchwork\CodeManipulation\Actions\RedefinitionOfNew\publicizeConstructors;

final class Default_Template implements Template_Interface {

	protected $post_type_name = 'pigeon_templates';

	protected $template;

	public function __construct( $template = null ) {
		if ( $template ) {
			$this->get_template( $template );
		}
	}

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
			'show_in_rest' => true,
		];
		\register_post_type( $this->post_type_name, $args );

		$this->create_default_template();
	}

	public function create_default_template() {
		// check if the default template exists, and create it
	}

	public function set_template( \WP_Post $template ) {
		$this->template = $template;
		return $this;
	}
	public function get_template( $template ) {

		if ( is_int( $template ) ) {
			$template_post = get_post( $template );
			if ( $template_post instanceof \WP_Post ) {
				return $this->set_template( $template_post );
			}
		}

		$query = new \WP_Query( [
			'posts_per_page' => 1,
			'post_type' => $this->post_type_name,
			'post_title' => $template,
		] );

		if ( $query->have_posts() ) {
			return $this->set_template( $query->next_post() );
		}

		return $this;
	}

	public function set_args( ...$args ) {
		$this->args = $args;
	}

	public function validate() {
		return true;
	}

	public function render( Entry $entry ) {
		return 'hello';
	}

	public function register_template_path( $templates ) {
		$templates[] = STELLARWP_PIGEON_PATH;
		return $templates;
	}

	public function replace_template( $template ) {
		if ( ! is_singular( $this->post_type_name ) ) {
			return $template;
		}

		$original_template = $template;

		$template = STELLARWP_PIGEON_PATH . 'src/views/pigeon-email-template.php';

		if ( \class_exists( 'Tribe__Events__Templates' ) ) {
			$template = \Tribe__Events__Templates::getTemplateHierarchy( 'pigeon-email-template.php' );
		}

		if ( \class_exists( 'Tribe__Tickets__Templates' ) ) {
			$template = \Tribe__Tickets__Templates::get_template_hierarchy( 'pigeon-email-template.php' );
		}

		return apply_filters( 'stellarwp_pigeon_email_template', $template, $original_template );
	}
}