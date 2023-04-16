<?php

namespace wpunit\Delivery;

use StellarWP\Pigeon\Delivery\Envelope;
use StellarWP\Pigeon\Delivery\Modules\Mail;

class EnvelopeTest extends \Codeception\TestCase\WPTestCase {

	public function test_get_modules() {
		$default_modules = [ Mail::class ];
		$this->assertEquals( $default_modules, Envelope::get_modules() );

	}
}