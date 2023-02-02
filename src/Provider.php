<?php

namespace StellarWP\Pigeon;

use StellarWP\Pigeon\Templates\DefaultTemplate;

class Provider extends \tad_DI52_ServiceProvider {

	private $has_registered = false;

	public function register() {
		if ( $this->has_registered ) {
			return false;
		}

		$this->register_filters();
		$this->register_actions();
		$this->has_registered = true;
		return true;
	}

	/**
	 * @return mixed
	 */
	public function register_actions() {
		add_action( 'init', [ $this, 'register_templates' ] );

	}

	public function register_filters() {

	}

	public function register_templates() {
		$this->container->make( DefaultTemplate::class )->register();
	}

}