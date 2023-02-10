<?php

namespace StellarWP\Pigeon\Scheduling;

use StellarWP\Pigeon\Models\Entry;

class Action_Scheduler {

	const SCHEDULED_TIME_OFFSET = 60;

	public function dispatch( $batch ) {
		foreach( $batch as $entry ) {
			$envelope = Entry::get_entry_envelope( $entry );
			$sender = new $envelope( $entry );
			$sender->send();
		}
	}
	public function register_main_schedule() {
		if ( false === as_has_scheduled_action( 'stellarwp_pigeon_schedule_default' ) ) {
			as_schedule_recurring_action( time(), MINUTE_IN_SECONDS, 'stellarwp_pigeon_schedule_default', array(), '', true );
		}

		add_action( 'stellarwp_pigeon_schedule_default', [ $this, 'schedule' ] );
	}

	public function schedule() {
		$batch = get_posts(); // max size of ready to send entries
		as_schedule_single_action( static::SCHEDULED_TIME_OFFSET + time(), 'stellarwp_pigeon_dispatch', $batch, 'pigeon', true );
	}
}