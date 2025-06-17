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

use lucatume\DI52\ServiceProvider;
use StellarWP\Pigeon\Contracts\Container;
use StellarWP\Pigeon\Contracts\Task;
use StellarWP\Pigeon\Tables\Tasks as Tasks_Table;
use RuntimeException;
use Exception;
use Throwable;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Exceptions\PigeonTaskException;

/**
 * Pigeon's regulator.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 * @property Container $container
 */
class Regulator extends ServiceProvider {
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
		$this->process_task_hook = sprintf( $this->process_task_hook, Provider::get_hook_prefix() );
	}

	/**
	 * Registers the regulator.
	 *
	 * @since TBD
	 */
	public function register(): void {
		add_action( $this->process_task_hook, [ $this, 'process_task' ], 10, 2 );
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
	 * @throws RuntimeException If the task fails to be scheduled or inserted into the database.
	 */
	protected function dispatch_callback( Task $task, int $delay ): void {
		$args       = $task->get_args();
		$group      = $task->get_group();
		$task_class = get_class( $task );
		$args_hash  = $args ? md5( wp_json_encode( $args ) ) : '';

		if ( as_has_scheduled_action( $this->process_task_hook, [ $task_class, $args_hash ], $group ) ) {
			return;
		}

		try {
			DB::beginTransaction();

			$action_id = as_schedule_single_action(
				time() + $delay,
				$this->process_task_hook,
				[ $task_class, $args_hash ],
				$group,
				$task->is_unique(),
				$task->get_priority()
			);

			if ( ! $action_id ) {
				throw new RuntimeException( 'Failed to schedule the task.' );
			}

			$result = Tasks_Table::insert(
				[
					'action_id' => $action_id,
					'args_hash' => $args_hash,
					'args'      => wp_json_encode( $args ),
				]
			);

			if ( ! $result ) {
				throw new RuntimeException( 'Failed to insert the task into the database.' );
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
			do_action( 'pigeon_' . Provider::get_hook_prefix() . '_task_scheduling_failed', $task, $e );
		}
	}

	/**
	 * Processes a task.
	 *
	 * @since TBD
	 *
	 * @param string $task_class     The task class.
	 * @param string $task_args_hash The task arguments hash.
	 *
	 * @throws RuntimeException    If no action ID is found, no Pigeon task is found with the action ID, or the task arguments hash does not match the expected hash.
	 * @throws PigeonTaskException If the task fails to be processed.
	 * @throws Exception           If the task fails to be processed.
	 * @throws Throwable           If the task fails to be processed.
	 */
	public function process_task( string $task_class, string $task_args_hash = '' ): void {
		if ( ! class_exists( $task_class ) ) {
			throw new RuntimeException( 'The task class does not exist.' );
		}

		if ( ! $this->current_action_id ) {
			throw new RuntimeException( 'No action ID found.' );
		}

		$task = Tasks_Table::get_by_action_id( $this->current_action_id, $task_class );

		if ( ! $task ) {
			throw new RuntimeException( 'No Pigeon task found with action ID ' . $this->current_action_id . '.' );
		}

		try {
			$task->process();
		} catch ( PigeonTaskException $e ) {
			if ( $task->should_retry() ) {
				$this->dispatch( $task, $task->get_retry_delay() );
				return;
			}
			// We need to later handle this.
			throw $e;
		} catch ( Exception $e ) {
			// We need to later handle this.
			throw $e;
		} catch ( Throwable $e ) {
			// We need to later handle this.
			throw $e;
		}
	}
}
