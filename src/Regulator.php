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
 * @package StellarWP\Pigeon
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
	 * The scheduled tasks.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $scheduled_tasks = [];

	/**
	 * The tasks that failed to be processed.
	 *
	 * This is used to track tasks that failed to be processed so that they can be retried.
	 *
	 * @since TBD
	 *
	 * @var Task[]
	 */
	protected array $failed_tasks = [];

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
		add_action( 'action_scheduler_after_process_queue', [ $this, 'handle_reschedule_of_failed_task' ], 1, 0 );
	}

	/**
	 * Handles the rescheduling of a failed task.
	 *
	 * @since TBD
	 */
	public function handle_reschedule_of_failed_task(): void {
		if ( empty( $this->failed_tasks ) ) {
			return;
		}

		foreach ( $this->failed_tasks as $offset => $task ) {
			$this->dispatch( $task, $task->get_retry_delay() );
			unset( $this->failed_tasks[ $offset ] );
		}
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
	 *
	 * @return self The regulator instance.
	 */
	public function dispatch( Task $task, int $delay = 0 ): self {
		if ( did_action( 'init' ) || doing_action( 'init' ) ) {
			$this->dispatch_callback( $task, $delay );
			return $this;
		}

		add_action(
			'init',
			function () use ( $task, $delay ): void {
				$this->dispatch_callback( $task, $delay );
			},
			10
		);

		return $this;
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
				false,
				$task->get_priority()
			);

			if ( ! $action_id ) {
				throw new RuntimeException( 'Failed to schedule the task.' );
			}

			$task->set_action_id( $action_id );

			$this->scheduled_tasks[] = $task->save();

			$log_data = [
				'action_id'   => $action_id,
				'current_try' => $task->get_current_try(),
			];

			if ( $previous_action_id ) {
				$this->log_rescheduled(
					$task->get_id(),
					array_merge(
						$log_data,
						[
							'previous_action_id' => $previous_action_id,
						]
					)
				);
			} else {
				$this->log_created( $task->get_id(), $log_data );
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
	 * Gets the last scheduled task ID.
	 *
	 * @since TBD
	 *
	 * @return ?int The last scheduled task ID.
	 */
	public function get_last_scheduled_task_id(): ?int {
		return empty( $this->scheduled_tasks ) ? null : end( $this->scheduled_tasks );
	}

	/**
	 * Gets the process task hook.
	 *
	 * @since TBD
	 *
	 * @return string The process task hook.
	 */
	public function get_hook(): string {
		return $this->process_task_hook;
	}

	/**
	 * Busts the runtime cached tasks.
	 *
	 * @since TBD
	 */
	public function bust_runtime_cached_tasks(): void {
		$this->scheduled_tasks = [];
	}

	/**
	 * Processes a task.
	 *
	 * @since TBD
	 *
	 * @param string $args_hash The arguments hash.
	 *
	 * @throws RuntimeException    If no action ID is found, no Pigeon task is found with the action ID, or the task arguments hash does not match the expected hash.
	 * @throws PigeonTaskException If the task fails to be processed.
	 * @throws Exception           If the task fails to be processed.
	 * @throws Throwable           If the task fails to be processed.
	 */
	public function process_task( string $args_hash ): void {
		$task = null;

		if ( ! $this->current_action_id ) {
			$task = Tasks_Table::get_by_args_hash( $args_hash );

			if ( ! $task ) {
				throw new RuntimeException( 'No Pigeon task found with args hash ' . $args_hash . '.' );
			}

			$task = array_shift( $task );
		}

		$task ??= Tasks_Table::get_by_action_id( $this->current_action_id );

		if ( ! $task ) {
			throw new RuntimeException( 'No Pigeon task found with action ID ' . $this->current_action_id . '.' );
		}

		$log_data = [
			'action_id'   => $this->current_action_id,
			'current_try' => $task->get_current_try(),
		];

		try {
			try {
				if ( $task->get_current_try() > 0 ) {
					$this->log_retrying( $task->get_id(), $log_data );
				} else {
					$this->log_starting( $task->get_id(), $log_data );
				}

				$task->process();

				$this->log_finished( $task->get_id(), $log_data );
			} catch ( PigeonTaskException $e ) {
				throw $e;
			}
		} catch ( Exception $e ) {
			/**
			 * Fires when a task fails to be processed.
			 *
			 * @since TBD
			 *
			 * @param Task      $task The task that failed to be processed.
			 * @param Exception $e    The exception that was thrown.
			 */
			do_action( 'pigeon_' . Config::get_hook_prefix() . '_task_failed', $task, $e );

			if ( $this->should_retry( $task ) ) {
				throw new PigeonTaskException( __( 'The task failed, but will be retried.', 'stellarwp-pigeon' ) );
			}

			$this->log_failed( $task->get_id(), array_merge( $log_data, [ 'exception' => $e->getMessage() ] ) );
			throw $e;
		} catch ( Throwable $e ) {
			/**
			 * Fires when a task fails to be processed.
			 *
			 * @since TBD
			 *
			 * @param Task      $task The task that failed to be processed.
			 * @param Throwable $e    The exception that was thrown.
			 */
			do_action( 'pigeon_' . Config::get_hook_prefix() . '_task_failed', $task, $e );

			if ( $this->should_retry( $task ) ) {
				throw new PigeonTaskException( __( 'The task failed, but will be retried.', 'stellarwp-pigeon' ) );
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
		if ( 0 === $task->get_max_retries() ) {
			return false;
		}

		if ( $task->get_current_try() >= $task->get_max_retries() ) {
			return false;
		}

		$task->set_current_try( $task->get_current_try() + 1 );

		$this->failed_tasks[] = $task;

		return true;
	}
}
