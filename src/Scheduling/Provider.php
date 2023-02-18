<?php

namespace StellarWP\Pigeon\Scheduling;

use PHP_CodeSniffer\Tests\Core\Tokenizer\BackfillEnumTest;
use StellarWP\Pigeon\Delivery\Batch;

class Provider extends \tad_DI52_ServiceProvider {

	public function register() {
		$this->container->singleton( Action_Scheduler::class );

		$this->load_action_scheduler();
		$this->register_actions();
	}
	public function register_actions() {
		add_action( 'init', [ $this, 'load_action_scheduler'] );
		add_action( 'init', [ $this, 'register_schedules'], 20 );
		add_action( 'stellarwp_pigeon_dispatch', [ $this, 'dispatch' ] );
	}

	public function register_schedules() {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->register_main_schedule();
	}

	public function dispatch( Batch $batch ) {
		$batch->dispatch();
	}

	/**
	 * Loads Action Scheduler late, after the `plugins_loaded` hook, at the
	 * start of the `init` one, so if a plugin has loaded it, we're good.
	 *
	 * @since TBD
	 */
	public function load_action_scheduler() {
		if ( function_exists( 'as_has_scheduled_action' ) || class_exists( 'Tribe__Main' ) ) {
			return;
		}

		require_once STELLARWP_PIGEON_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
	}
}