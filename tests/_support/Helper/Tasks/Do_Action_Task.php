<?php
declare( strict_types=1 );

namespace StellarWP\Shepherd\Tests\Tasks;

use StellarWP\Shepherd\Abstracts\Task_Abstract;

class Do_Action_Task extends Task_Abstract {
	public function get_task_prefix(): string {
		return 'test_do_action_';
	}

	public function get_task_name(): string {
		return 'shepherd_test_do_action_task';
	}

	public function process(): void {
		do_action( $this->get_task_name() );
	}
}