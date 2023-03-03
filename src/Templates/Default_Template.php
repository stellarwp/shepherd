<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Tags\Collection;

final class Default_Template implements Template_Interface {

	protected $post_type_name = 'pigeon_templates';

	protected $template;

	public function __construct( $template = null, $entry = null ) {
		if ( $template ) {
			$this->get_template( $template );
		}

		if ( $entry instanceof Entry ) {
			$this->set_entry( $entry );
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

	public function set_entry( Entry $entry ) {
		$this->entry = $entry;
		return $this;
	}

	public function get_entry() :Entry {
		return $this->entry;
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

	public function get_key( $key ) {
		return $this->template->{$key};
	}

	public function get_template_body_raw() {
		return $this->template->post_content;
	}

	public function set_rendered_content( $content ) {
		$this->rendered = $content;
	}

	public function get_rendered_content() {
		if ( empty( $this->rendered ) ) {
			$this->rendered = $this->get_template_body_raw();
		}

		return $this->rendered;
	}

	public function render() {
		$tags = new Collection();

		foreach( $tags->get_all() as $tag ) {
			if ( false === strpos( $this->get_rendered_content(), $tag->get_tag_name() ) ) {
				continue;
			}

			$this->set_rendered_content( $tag->set_entry( $this->get_entry() )->set_template( $this )->compose() );
		}

		return $this->format_to_display();
	}

	public function format_to_display() {
		$post = $this->template;
		$post->post_content = $this->get_rendered_content();

		setup_postdata( $post );

		\ob_start();
		include $this->pigeon_email_template( null, true );
		$this->rendered = \ob_get_contents();
		\ob_end_clean();

		\wp_reset_postdata();

		return $this;
	}

	public function register_template_path( $templates ) {
		$templates[] = STELLARWP_PIGEON_PATH;
		return $templates;
	}

	public function pigeon_email_template( $template, $force = false ) {
		if ( ! is_singular( $this->post_type_name ) && ! $force ) {
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