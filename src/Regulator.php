<?php
/**
 * Pigeon's regulator.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use StellarWP\Pigeon\Abstracts\Provider_Abstract;
use StellarWP\ContainerContract\ContainerInterface as Container;
use StellarWP\Pigeon\Contracts\Task;
use StellarWP\Pigeon\Tables\Tasks as Tasks_Table;
use RuntimeException;
use Exception;
use Throwable;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Exceptions\PigeonTaskException;
use StellarWP\Pigeon\Exceptions\PigeonTaskAlreadyExistsException;
use StellarWP\Pigeon\Traits\Loggable;

/**
 * Pigeon's regulator.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Regulator extends Provider_Abstract {
	use Loggable;

	/**
	 * The process task hook.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $process_task_hook = 'pigeon_%s_process_task';

	/**
	 * The action ID being processed.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected int $current_action_id = 0;

	/**
	 * The regulator's constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The container.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );
		$this->process_task_hook = sprintf( $this->process_task_hook, Config::get_hook_prefix() );
	}

	/**
	 * Registers the regulator.
	 *
	 * @since TBD
	 */
	public function register(): void {
		add_action( $this->process_task_hook, [ $this, 'process_task' ] );
		add_action( 'action_scheduler_before_execute', [ $this, 'track_current_action' ], 1, 1 );
		add_action( 'action_scheduler_after_execute', [ $this, 'untrack_action' ], 1, 0 );
		add_action( 'action_scheduler_execution_ignored', [ $this, 'untrack_action' ], 1, 0 );
		add_action( 'action_scheduler_failed_execution', [ $this, 'untrack_action' ], 1, 0 );
	}

	/**
	 * Track specified action.
	 *
	 * @since TBD
	 *
	 * @param int $action_id Action ID to track.
	 */
	public function track_current_action( int $action_id ): void {
		$this->current_action_id = $action_id;
	}

	/**
	 * Un-track action.
	 *
	 * @since TBD
	 */
	public function untrack_action(): void {
		$this->current_action_id = 0;
	}

	/**
	 * Dispatches a task to be processed later.
	 *
	 * @since TBD
	 *
	 * @param Task $task  The task to dispatch.
	 * @param int  $delay The delay in seconds before the task is processed.
	 */
	public function dispatch( Task $task, int $delay = 0 ): void {
		$delay = $task->is_debouncable() ? $delay + $task->get_debounce_delay() : $delay;

		if ( did_action( 'init' ) || doing_action( 'init' ) ) {
			$this->dispatch_callback( $task, $delay );
			return;
		}

		add_action(
			'init',
			function () use ( $task, $delay ): void {
				$this->dispatch_callback( $task, $delay );
			},
			10
		);
	}

	/**
	 * Dispatches a task to be processed later.
	 *
	 * @since TBD
	 *
	 * @param Task $task  The task to dispatch.
	 * @param int  $delay The delay in seconds before the task is processed.
	 *
	 * @throws RuntimeException                 If the task fails to be scheduled or inserted into the database.
	 * @throws PigeonTaskAlreadyExistsException If the task is already scheduled.
	 */
	protected function dispatch_callback( Task $task, int $delay ): void {
		$group     = $task->get_group();
		$args_hash = $task->get_args_hash();

		try {
			DB::beginTransaction();

			if ( Action_Scheduler_Methods::has_scheduled_action( $this->process_task_hook, [ $args_hash ], $group ) ) {
				throw new PigeonTaskAlreadyExistsException( 'The task is already scheduled.' );
			}

			$previous_action_id = $task->get_action_id();

			$action_id = Action_Scheduler_Methods::schedule_single_action(
				time() + $delay,
				$this->process_task_hook,
				[ $args_hash ],
				$group,
				$task->is_unique(),
				$task->get_priority()
			);

			if ( ! $action_id ) {
				throw new RuntimeException( 'Failed to schedule the task.' );
			}

			$task->set_action_id( $action_id );

			$task->save();

			if ( $previous_action_id ) {
				$this->log_rescheduled(
					$task->get_id(),
					[
						'action_id'          => $action_id,
						'previous_action_id' => $previous_action_id,
					] 
				);
			} else {
				$this->log_created( $task->get_id(), [ 'action_id' => $action_id ] );
			}

			DB::commit();
		} catch ( RuntimeException $e ) {
			DB::rollback();
			/**
			 * Fires when a task fails to be scheduled or inserted into the database.
			 *
			 * @since TBD
			 *
			 * @param Task             $task The task that failed to be scheduled or inserted into the database.
			 * @param RuntimeException $e    The exception that was thrown.
			 */
			do_action( 'pigeon_' . Config::get_hook_prefix() . '_task_scheduling_failed', $task, $e );
		} catch ( PigeonTaskAlreadyExistsException $e ) {
			DB::rollback();
			/**
			 * Fires when a task is already scheduled.
			 *
			 * @since TBD
			 *
			 * @param Task $task The task that is already scheduled.
			 */
			do_action( 'pigeon_' . Config::get_hook_prefix() . '_task_already_scheduled', $task );
		}
	}

	/**
	 * Processes a task.
	 *
	 * @since TBD
	 *
	 * @throws RuntimeException    If no action ID is found, no Pigeon task is found with the action ID, or the task arguments hash does not match the expected hash.
	 * @throws PigeonTaskException If the task fails to be processed.
	 * @throws Exception           If the task fails to be processed.
	 * @throws Throwable           If the task fails to be processed.
	 */
	public function process_task(): void {
		if ( ! $this->current_action_id ) {
			throw new RuntimeException( 'No action ID found.' );
		}

		$task = Tasks_Table::get_by_action_id( $this->current_action_id );

		if ( ! $task ) {
			throw new RuntimeException( 'No Pigeon task found with action ID ' . $this->current_action_id . '.' );
		}

		$log_data = [
			'class'       => get_class( $task ),
			'args'        => $task->get_args(),
			'action_id'   => $this->current_action_id,
			'current_try' => $task->get_current_try(),
		];

		try {
			if ( $task->get_current_try() > 1 ) {
				$this->log_retrying( $task->get_id(), $log_data );
			} else {
				$this->log_starting( $task->get_id(), $log_data );
			}

			$task->process();

			$this->log_finished( $task->get_id(), $log_data );
		} catch ( PigeonTaskException $e ) {
			if ( $this->should_retry( $task ) ) {
				return;
			}

			$this->log_failed( $task->get_id(), array_merge( $log_data, [ 'exception' => $e->getMessage() ] ) );
			throw $e;
		} catch ( Exception $e ) {
			if ( $this->should_retry( $task ) ) {
				return;
			}

			$this->log_failed( $task->get_id(), array_merge( $log_data, [ 'exception' => $e->getMessage() ] ) );
			throw $e;
		} catch ( Throwable $e ) {
			if ( $this->should_retry( $task ) ) {
				return;
			}

			$this->log_failed( $task->get_id(), array_merge( $log_data, [ 'exception' => $e->getMessage() ] ) );
			throw $e;
		}
	}

	/**
	 * Determines if the task should be retried.
	 *
	 * @since TBD
	 *
	 * @param Task $task The task.
	 * @return bool Whether the task should be retried.
	 */
	protected function should_retry( Task $task ): bool {
		if ( ! $task->should_retry() ) {
			return false;
		}

		$task->set_current_try( $task->get_current_try() + 1 );
		$this->dispatch( $task, $task->is_debouncable() ? $task->get_debounce_delay_on_failure() : $task->get_retry_delay() );
		return true;
	}
}
