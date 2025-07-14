<?php

namespace StellarWP\Shepherd\Tests\Traits;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use StellarWP\Shepherd\Log;

trait With_Log_Snapshot {
	use SnapshotAssertions;

	protected function assertMatchesLogSnapshot( array $logs ): void {
		$log_array = array_map(
			fn( Log $log ) => $log->to_array(),
			$logs
		);

		$action_ids = array_values(
			array_unique(
				array_map(
					fn( array $log ) => $log['action_id'],
					$log_array
				)
			)
		);

		$previous_action_ids = array_values(
			array_filter(
				array_unique(
					array_map(
						fn( array $log ) => json_decode( $log['entry'], true )['context']['previous_action_id'] ?? null,
						$log_array
					)
				)
			)
		);

		$json = wp_json_encode( $log_array, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace(
			wp_list_pluck( $log_array, 'id' ),
			[ '{LOG_ID_1}', '{LOG_ID_2}', '{LOG_ID_3}', '{LOG_ID_4}', '{LOG_ID_5}' ],
			$json
		);

		$action_placeholders = array_fill( 0, count( $action_ids ), '{ACTION_ID_' );

		foreach( $action_placeholders as $key => $placeholder ) {
			$json = str_replace(
				$action_ids[$key],
				$placeholder . ($key + 1) . '}',
				$json
			);
		}

		$previous_action_placeholders = array_fill( 0, count( $previous_action_ids ), '{PREVIOUS_ACTION_ID_' );

		foreach( $previous_action_placeholders as $key => $placeholder ) {
			$json = str_replace(
				$previous_action_ids[$key],
				$placeholder . ($key + 1) . '}',
				$json
			);
		}

		$json = str_replace(
			(string) $logs[0]->get_task_id(),
			'{TASK_ID}',
			$json
		);

		$this->assertMatchesJsonSnapshot( $json );
	}
}
