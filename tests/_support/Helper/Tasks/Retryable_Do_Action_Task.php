<?php
declare( strict_types=1 );

namespace StellarWP\Shepherd\Tests\Tasks;

use StellarWP\Shepherd\Abstracts\Task_Abstract;
use Exception;

class Retryable_Do_Action_Task extends Task_Abstract {

	protected static int $processed = 0;

	public function get_task_prefix(): string {
		return 'retry_action_';
	}

	public function get_task_name(): string {
		return 'shepherd_retry_do_action_task';
	}

	public function get_max_retries(): int {
		return 2;
	}

	public function process(): void {
		static::$processed++;

		if ( static::$processed === 1 ) {
			throw new Exception( 'Mock Action failure' );
		}

		do_action( $this->get_task_name() );
	}
}