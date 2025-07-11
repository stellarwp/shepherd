<?php
declare( strict_types=1 );

namespace StellarWP\Shepherd\Tests\Tasks;

use StellarWP\Shepherd\Abstracts\Task_Abstract;

class Internal_Counting_Task extends Task_Abstract {
	public static int $processed = 0;

	public function get_task_prefix(): string {
		return 'inter_count_';
	}

	public function get_task_name(): string {
		return 'shepherd_retry_do_action_task';
	}

	public function process(): void {
		static::$processed++;

		do_action( $this->get_task_name() );
	}
}