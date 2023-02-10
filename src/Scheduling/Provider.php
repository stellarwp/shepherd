<?php

namespace StellarWP\Pigeon\Scheduling;

use StellarWP\Pigeon\Pigeon;

class Provider extends \tad_DI52_ServiceProvider {

	public function register() {
		$this->container->singleton( Action_Scheduler::class );

		$this->load_action_scheduler();
		$this->register_actions();
	}
	public function register_actions() {
		add_action( 'plugins_loaded', [ $this, 'register_schedules'] );
		add_action( 'stellarwp_pigeon_dispatch', [ $this, 'dispatch' ] );
	}

	public function register_schedules() {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->register_main_schedule();
	}

	public function dispatch( $batch ) {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->dispatch( $batch );
	}

	/**
	 * Loads the Action Scheduler library by loading the main plugin file shipped with
	 * this plugin.
	 *
	 * This method would, usually, run on the `plugins_loaded` action and might, in that
	 * case, further delay the loading of the Action Scheduler library to the `init` action.
	 *
	 * @since TBD
	 */
	private function load_action_scheduler() {
		$load_action_scheduler = [ $this, 'load_action_scheduler_late' ];

		if ( ! has_action( 'tec_events_custom_tables_v1_load_action_scheduler', $load_action_scheduler ) ) {
			// Add a custom action that will allow triggering the load of Action Scheduler.
			add_action( 'tec_events_custom_tables_v1_load_action_scheduler', $load_action_scheduler );
		}

		/*
		 * We do not sense around for of the functions defined by Action Scheduler by design:
		 * Action Scheduler will take care of loading the most recent version. If we looked
		 * for some of Action Scheduler API functions, then, we would let a possibly older
		 * version load instead of ours just because it did init Action Scheduler before
		 * this plugin.
		 */
		if ( did_action( 'plugins_loaded' ) || doing_action( 'plugins_loaded' ) ) {
			add_action( 'init', $load_action_scheduler, - 99999 );

			return;
		}

		$action_scheduler_file = TEC::instance()->plugin_path . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
		require_once $action_scheduler_file;
	}

	/**
	 * Loads Action Scheduler late, after the `plugins_loaded` hook, at the
	 * start of the `init` one.
	 *
	 * Action Scheduler does support loading after the `plugins_loaded` hook, but
	 * not during it. The provider will register exactly during the `plugins_loaded`
	 * action, so we need to retry setting up Action Scheduler again.
	 *
	 * @since TBD
	 */
	public function load_action_scheduler_late() {
		$action_scheduler_file = Pigeon::instance()->plugin_path . '/vendor/woocommerce/action-scheduler/action-scheduler.php';
		require_once $action_scheduler_file;
	}
}