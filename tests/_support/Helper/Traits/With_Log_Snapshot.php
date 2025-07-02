<?php

namespace StellarWP\Pigeon\Tests\Traits;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use StellarWP\Pigeon\Log;
use StellarWP\Pigeon\Tests\PigeonJsonSnapshot;

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

	/**
	 * Asserts the current JSON string matches the one stored in the snapshot file.
	 *
	 * If the snapshot file is not present the assertion will be skipped and the snapshot file will be generated.
	 *
	 * @param string        $current     The current JSON string.
	 * @param callable|null $dataVisitor A callable to manipulate the file contents before the assertion. The arguments
	 *                                   will be an the expected and the current values (strings).
	 */
	protected function assertMatchesJsonSnapshot($current, callable $dataVisitor = null)
	{
		$jsonSnapshot = new PigeonJsonSnapshot($current);
		if ($dataVisitor !== null) {
			$jsonSnapshot->setDataVisitor($dataVisitor);
		}
		$jsonSnapshot->assert();
	}
}
