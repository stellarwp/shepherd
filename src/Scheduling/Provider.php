<?php

namespace StellarWP\Pigeon\Scheduling;

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

		// Action Scheduler actions
		add_action( Action_Scheduler::DISPATCH_ACTION_NAME, [ $this, 'dispatch' ] );
		add_action( Action_Scheduler::SCHEDULE_ACTION_NAME, [ $this, 'process_batch' ] );

		// Testing AS without cron
		/*
		add_action( 'wp', function() {
			$db = new \ActionScheduler_DBStore();
			$action = $db->fetch_action(485);
			$as = new \ActionScheduler_Action( Action_Scheduler::DISPATCH_ACTION_NAME, $action->get_args() );
			$as->execute();
		}  );
		*/
	}

	public function register_schedules() {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->register_main_schedule();
	}

	public function process_batch() {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->process_new_batch();
	}

	public function dispatch( $entries ) {
		$batch = new Batch();
		$batch->set_entries( $entries );
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