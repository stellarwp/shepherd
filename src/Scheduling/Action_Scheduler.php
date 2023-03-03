<?php

namespace StellarWP\Pigeon\Scheduling;

use StellarWP\Pigeon\Delivery\Batch;

class Action_Scheduler {

	const SCHEDULE_TIME_OFFSET = 60;

	const SCHEDULE_ACTION_INTERVAL = 60;

	const SCHEDULE_ACTION_NAME = 'stellarwp_pigeon_schedule_default';

	const SCHEDULE_ACTIONS_GROUP = 'stellarwp_pigeon_schedule_group';

	const DISPATCH_ACTION_NAME = 'stellarwp_pigeon_dispatch';


	public function register_main_schedule() {
		if ( false === as_has_scheduled_action( static::SCHEDULE_ACTION_NAME) ) {
			as_schedule_recurring_action(
				$this->schedule_time(),
				$this->schedule_interval(),
				static::SCHEDULE_ACTION_NAME,
				[],
				static::SCHEDULE_ACTIONS_GROUP,
				true
			);
		}

		add_action( static::SCHEDULE_ACTION_NAME, [ $this, 'schedule' ] );
	}

	public function schedule() {
		as_schedule_single_action(
			$this->schedule_time(),
			static::DISPATCH_ACTION_NAME,
			[ new Batch() ],
			static::SCHEDULE_ACTIONS_GROUP,
			true
		);
	}

	public function schedule_time() {
		return time() + static::SCHEDULE_TIME_OFFSET;
	}

	public function schedule_interval() {
		return static::SCHEDULE_ACTION_INTERVAL;
	}
}