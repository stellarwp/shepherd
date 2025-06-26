<?php
declare( strict_types=1 );

namespace StellarWP\Pigeon\Tests\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class Do_Prefixed_Action_Task extends Task_Abstract {
	public function __construct( string $action_prefix ) {
		parent::__construct( $action_prefix );
	}

	public function get_task_prefix(): string {
		return 'do_pre_action_';
	}

	public function get_task_name(): string {
		return $this->get_args()['0'] . '_pigeon_test_do_action_task';
	}

	public function process(): void {
		do_action( $this->get_task_name() );
	}
}