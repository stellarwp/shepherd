<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class Retryable_Do_Action_Task extends Task_Abstract {
	protected static int $max_retries = 2;

	public function get_task_prefix(): string {
		return 'retry_do_action_';
	}

	public function get_task_name(): string {
		return 'pigeon_retry_do_action_task';
	}

	public function process(): void {
		do_action( $this->get_task_name() );
	}
}