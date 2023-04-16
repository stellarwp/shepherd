<?php

namespace StellarWP\Pigeon\Delivery\Modules;

class MailTest extends \Codeception\Test\Unit {

	public function test_can_intercept_mail() {
		$mail = new Mail();
		$intercept = $mail->intercept([]);

		$this->assertNull( $intercept );
	}
}
