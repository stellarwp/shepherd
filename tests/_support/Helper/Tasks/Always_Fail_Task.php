<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;
use Exception;

class Always_Fail_Task extends Task_Abstract {
	public function get_task_prefix(): string {
		return 'test_alw_fail_';
	}

	public function process(): void {
		throw new Exception( 'Always fail' );
	}

	public function get_max_retries(): int {
		return 1;
	}
}