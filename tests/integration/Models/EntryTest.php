<?php

namespace integration\Models;

use StellarWP\Pigeon\Models\Entry;

class EntryTest extends \Codeception\TestCase\WPTestCase {

	public function test_schedule_entry() {
		$entry = null; // failing on purpose
		$this->assertTrue( $entry instanceof Entry );
	}
}