<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class Do_Action_Task extends Task_Abstract {
	public function get_task_prefix(): string {
		return 'test_do_action_';
	}

	public function get_task_name(): string {
		return 'pigeon_test_do_action_task';
	}

	public function process(): void {
		do_action( $this->get_task_name() );
	}
}