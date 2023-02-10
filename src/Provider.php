<?php

namespace StellarWP\Pigeon;

use StellarWP\Pigeon\Config\Database;
use StellarWP\Pigeon\Scheduling\Action_Scheduler;
use StellarWP\Pigeon\Templates\Default_Template;
use StellarWP\Schema\Tables\Contracts\Table;

class Provider extends \tad_DI52_ServiceProvider {

	private $has_registered = false;

	public function register() {
		if ( $this->has_registered ) {
			return false;
		}

		$this->container->register( Scheduling\Provider::class );

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
		add_action( 'init', [ $this, 'register_main_schedule'] );
		add_action( 'plugins_loaded', [ $this, 'register_database' ], 2 );

	}

	public function register_filters() {

	}

	public function register_templates() {
		$this->container->make( Default_Template::class )->register();
	}

	public function register_database() {
		$this->container->make( Database::class )->register();
	}


	public function add( Table $table ) {
		$this->container->make( $table );
	}

}