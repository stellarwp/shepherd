<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use InvalidArgumentException;
use StellarWP\Shepherd\Exceptions\ShepherdTaskException;
use TypeError;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;

class Email_Test extends WPTestCase {
	use With_Uopz;
	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_email() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid email address(es): not-an-email' );
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
	public function it_should_throw_shepherd_exception_if_wp_mail_fails() {
		$email = new Email( 'test@test.com', 'Subject', 'Body' );

		$this->set_fn_return( 'wp_mail', false );

		$this->expectException( ShepherdTaskException::class );
		$email->process();
	}

	/**
	 * @test
	 */
	public function it_should_accept_multiple_recipients_separated_by_comma() {
		$email = new Email( 'test1@test.com, test2@test.com, test3@test.com', 'Subject', 'Body' );

		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $body, $headers = [], $attachments = [] ) use ( &$spy ) {
			$spy = [ $to, $subject, $body, $headers, $attachments ];
			return true;
		}, true );

		$email->process();

		$this->assertEquals( [ 'test1@test.com, test2@test.com, test3@test.com', 'Subject', 'Body', [], [] ], $spy );
	}

	/**
	 * @test
	 */
	public function it_should_accept_multiple_recipients_with_spaces() {
		$email = new Email( 'test1@test.com,   test2@test.com  ,test3@test.com', 'Subject', 'Body' );

		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $body, $headers = [], $attachments = [] ) use ( &$spy ) {
			$spy = [ $to, $subject, $body, $headers, $attachments ];
			return true;
		}, true );

		$email->process();

		$this->assertEquals( [ 'test1@test.com,   test2@test.com  ,test3@test.com', 'Subject', 'Body', [], [] ], $spy );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_email_in_multiple_recipients() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid email address(es): not-an-email, another-invalid' );
		new Email( 'test@test.com, not-an-email, valid@email.com, another-invalid', 'Subject', 'Body' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_empty_string_recipients() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Email recipients must be a non-empty string' );
		new Email( '', 'Subject', 'Body' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_whitespace_only_recipients() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Email recipients must be a non-empty string' );
		new Email( '   ', 'Subject', 'Body' );
	}

	/**
	 * @test
	 */
	public function it_should_accept_single_recipient() {
		$email = new Email( 'test@test.com', 'Subject', 'Body' );

		$spy = [];
		$this->set_fn_return( 'wp_mail', function ( $to, $subject, $body, $headers = [], $attachments = [] ) use ( &$spy ) {
			$spy = [ $to, $subject, $body, $headers, $attachments ];
			return true;
		}, true );

		$email->process();

		$this->assertEquals( [ 'test@test.com', 'Subject', 'Body', [], [] ], $spy );
	}
}
