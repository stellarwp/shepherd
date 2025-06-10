<?php

namespace StellarWP\Pigeon\Templates;

/**
 * Templating Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Registers Pigeon's template hooks
	 *
	 * @since TBD
	 */
	public function register(): void {
		$this->register_actions();
		$this->register_filters();
	}

	/**
	 * Registers entry points for Pigeon's template functionality
	 *
	 * @since TBD
	 */
	public function register_actions(): void {
		add_action( 'init', [ $this, 'register_templates' ] );
	}

	/**
	 * Registers entry points for Pigeon's template replacements
	 *
	 * @since TBD
	 */
	public function register_filters(): void {
		add_filter( 'tribe_events_template_paths', [ $this, 'register_pigeon_template_path' ] );
		add_filter( 'tribe_tickets_template_paths', [ $this, 'register_pigeon_template_path' ] );
		add_filter( 'template_include', [ $this, 'register_pigeon_template' ] );
	}

	/**
	 * Make the default template class
	 *
	 * @since TBD
	 */
	public function register_templates(): void {
		$this->container->make( Default_Template::class )->register();
	}

	/**
	 * Add Pigeon's template paths to the TEC system
	 *
	 * @since TBD
	 *
	 * @return array list of registered template paths
	 */
	public function register_pigeon_template_path( $templates ): array {
		return $this->container->make( Default_Template::class )->register_template_path( $templates );
	}

	/**
	 * Add Pigeon's template paths to WP Core
	 *
	 * @since TBD
	 *
	 * @return string the template path
	 */
	public function register_pigeon_template( $template ): string {
		return $this->container->make( Default_Template::class )->pigeon_email_template( $template );
	}
}
