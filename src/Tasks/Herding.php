<?php
/**
 * Shepherd's herding task.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Tasks;
 */

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Abstracts\Task_Abstract;
use StellarWP\Shepherd\Tables\Task_Logs;
use StellarWP\Shepherd\Tables\Tasks;
use StellarWP\DB\DB;

/**
 * Shepherd's herding task.
 *
 * @since TBD
 *
 * @package StellarWP\Shepherd\Tasks;
 */
class Herding extends Task_Abstract {
	/**
	 * Processes the herding task.
	 *
	 * @since TBD
	 */
	public function process(): void {
		DB::beginTransaction();

		/**
		* Filters the limit of tasks to herd in a single batch.
		*
		* @since TBD
		*
		* @param int $limit The limit of tasks to herd.
		*/
		$batch_size = max( 1, (int) apply_filters( 'shepherd_' . Config::get_hook_prefix() . '_herding_batch_limit', 500 ) );

		do {
			$task_ids = DB::get_col(
				DB::prepare(
					'SELECT DISTINCT(%i) FROM %i WHERE %i NOT IN (SELECT %i FROM %i) LIMIT %d',
					Tasks::uid_column(),
					Tasks::table_name(),
					'action_id',
					'action_id',
					DB::prefix( 'actionscheduler_actions' ),
					$batch_size,
				)
			);

			if ( empty( $task_ids ) ) {
				/**
				 * Fires when the herding task is processed.
				 *
				 * @since TBD
				 *
				 * @param Herding $task The herding task that was processed.
				 */
				do_action( 'shepherd_' . Config::get_hook_prefix() . '_herding_processed', $this );
				return;
			}

			$task_ids = implode( ',', array_unique( array_map( 'intval', $task_ids ) ) );

			DB::query(
				DB::prepare(
					"DELETE FROM %i WHERE task_id IN ({$task_ids})",
					Task_Logs::table_name(),
				)
			);

			DB::query(
				DB::prepare(
					"DELETE FROM %i WHERE %i IN ({$task_ids})",
					Tasks::table_name(),
					Tasks::uid_column(),
				)
			);
		} while ( ! empty( $task_ids ) );

		DB::commit();

		/**
		 * Fires when the herding task is processed.
		 *
		 * @since TBD
		 *
		 * @param Herding $task The herding task that was processed.
		 */
		do_action( 'shepherd_' . Config::get_hook_prefix() . '_herding_processed', $this );
	}

	/**
	 * Gets the herding task's hook prefix.
	 *
	 * @since TBD
	 *
	 * @return string The herding task's hook prefix.
	 */
	public function get_task_prefix(): string {
		return 'shepherd_tidy_';
	}
}
