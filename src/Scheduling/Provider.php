<?php

namespace StellarWP\Pigeon\Scheduling;

use StellarWP\Pigeon\Delivery\Batch;

/**
 * Scheduling Service Provider
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Registers Pigeon's scheduling hooks
	 *
	 * @since TBD
	 */
	public function register(): void {
		$this->container->singleton( Action_Scheduler::class );

		$this->load_action_scheduler();
		$this->register_actions();
	}

	/**
	 * Registers entry points for Pigeon's scheduling functionality
	 *
	 * @since TBD
	 */
	public function register_actions(): void {
		add_action( 'init', [ $this, 'load_action_scheduler' ] );
		add_action( 'init', [ $this, 'register_schedules' ], 20 );

		// Action Scheduler actions
		add_action( Action_Scheduler::DISPATCH_ACTION_NAME, [ $this, 'dispatch' ] );
		add_action( Action_Scheduler::SCHEDULE_ACTION_NAME, [ $this, 'process_batch' ] );
	}

	/**
	 * Registers Pigeon's main schedule
	 *
	 * @since TBD
	 */
	public function register_schedules(): void {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->register_main_schedule();
	}

	/**
	 * Process new batches of entries as part of the scheduled tasks
	 *
	 * @since TBD
	 */
	public function process_batch(): void {
		$action_scheduler = $this->container->make( Action_Scheduler::class );
		$action_scheduler->process_new_batch();
	}

	/**
	 * Dispatch a new batch of entries to their delivery modules.
	 *
	 * @since TBD
	 */
	public function dispatch( $entries ): void {
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
	public function load_action_scheduler(): void {
		if ( function_exists( 'as_has_scheduled_action' ) || class_exists( 'Tribe__Main' ) ) {
			return;
		}

		require_once STELLARWP_PIGEON_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
	}
}
