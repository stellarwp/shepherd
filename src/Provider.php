<?php

namespace StellarWP\Pigeon;

use lucatume\DI52\ServiceProvider;
use StellarWP\Pigeon\Templates\DefaultTemplate;

class Provider extends ServiceProvider {

	public function register() {
		$this->register_filters();
		$this->register_actions();
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
		tribe( DefaultTemplate::class )->register();
	}

}