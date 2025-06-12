<?php

namespace wpunit\Delivery;

use StellarWP\Pigeon\Delivery\Envelope;
use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Models\Entry;

class EnvelopeTest extends \Codeception\TestCase\WPTestCase {

	public function test_get_modules() {
		$default_modules = [ Mail::class ];
		$envelope = new Envelope( Entry::instance() );
		$this->assertEquals( $default_modules, $envelope->get_available_modules() );

	}
}