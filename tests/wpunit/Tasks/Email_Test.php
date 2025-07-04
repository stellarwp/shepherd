<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use InvalidArgumentException;
use StellarWP\Pigeon\Exceptions\PigeonTaskException;
use TypeError;
use StellarWP\Pigeon\Tests\Traits\With_Uopz;

class Email_Test extends WPTestCase {
	use With_Uopz;
	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_email() {
		$this->expectException( InvalidArgumentException::class );
		new Email( 'not-an-email', 'Subject', 'Body' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_subject() {
		$this->expectException( TypeError::class );
		/** @var string $subject */
		$subject = 123;
		new Email( 'test@test.com', $subject, 'Body' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_body() {
		$this->expectException( TypeError::class );
		/** @var string $body */
		$body = [];
		new Email( 'test@test.com', 'Subject', $body );
	}

	/**
	 * @test
	 */
	public function it_should_process_and_call_wp_mail() {
		$email = new Email( 'test@test.com', 'Subject', 'Body' );

		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $body, $headers = [], $attachments = [] ) use ( &$spy ) {
			$spy = [ $to, $subject, $body, $headers, $attachments ];
			return true;
		}, true );

		$email->process();

		$this->assertEquals( [ 'test@test.com', 'Subject', 'Body', [], [] ], $spy );
	}

	/**
	 * @test
	 */
	public function it_should_throw_pigeon_exception_if_wp_mail_fails() {
		$email = new Email( 'test@test.com', 'Subject', 'Body' );

		$this->set_fn_return( 'wp_mail', false );

		$this->expectException( PigeonTaskException::class );
		$email->process();
	}
}
