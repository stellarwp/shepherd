<?php

namespace integration\Delivery\Modules;

use StellarWP\Pigeon\Delivery\Modules\Mail;

class MailTest extends \Codeception\TestCase\WPTestCase {

	public function test_mail_init_returns_instance() {
		$mail = Mail::init();
		$this->assertTrue( $mail instanceof Mail );
	}

}