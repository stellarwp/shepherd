<?php

namespace wpunit\Delivery\Modules;

use StellarWP\Pigeon\Delivery\Envelope;
use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Models\Entry;

class MailTest extends \Codeception\TestCase\WPTestCase {

	public function setUp(): void {
		if ( ! defined( 'STELLARWP_PIGEON_PATH' ) ) {
			define( 'STELLARWP_PIGEON_PATH', dirname( __DIR__ ) . '/' );
		}

		parent::setUp();
	}

	public function test_can_intercept_mail() {
		$mail      = new Mail();
		$intercept = $mail->intercept( $this->mock_email_args() );

		// Do not intercept emails unless explicitly instructed
		$this->assertNull( $intercept );

		// Do not intercept emails when instructed by the Module Active Signature header
		$module_active_signature = Envelope::MODULE_ACTIVE_SIGNATURE;
		$intercept = $mail->intercept( $this->mock_email_args( [ 'headers' => [ "{$module_active_signature}: true" ] ] ) );
		$this->assertNull( $intercept );

		// Intercept emails when instructed by the Process Signature Header
		$module_process_signature = Envelope::MODULE_PROCESS_SIGNATURE;
		$intercept = $mail->intercept( $this->mock_email_args( [ 'headers' => [ "{$module_process_signature}: true" ] ] ) );
		$this->assertTrue( $intercept );

		// Intercept emails when instructed by the flag filter
		add_filter( 'stellarwp_pigeon_process_message', '__return_true' );
		$intercept = $mail->intercept( $this->mock_email_args() );
		$this->assertTrue( $intercept );
		remove_filter( 'stellarwp_pigeon_process_message', '__return_true' );
	}

	public function test_can_send_mail() {
		$entry = $this->create_entry();
		Mail::send( $entry );
		$this->assertEquals( 'complete', $entry->get( 'status' ) );
	}

	private function create_entry( $args = [] ) {
		$entry = new Entry();
		$mail  = $this->mock_email_args( $args );
		$entry->set_data( $mail );

		return $entry;
	}

	private function mock_email_args( $args = [] ) {
		return [
			null,
			wp_parse_args( $args, [
				'to'          => 'some@email.org',
				'subject'     => 'Test',
				'message'     => '<!DOCTYPE HTML yadayada',
				'headers'     => [
					'Content-type: text/html',
				],
				'attachments' => [],
			] ),
		];
	}

}