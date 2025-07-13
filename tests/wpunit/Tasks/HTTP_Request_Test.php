<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use InvalidArgumentException;
use StellarWP\Shepherd\Exceptions\ShepherdTaskException;
use StellarWP\Shepherd\Exceptions\ShepherdTaskFailWithoutRetryException;
use TypeError;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use WP_Error;

class HTTP_Request_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * @test
	 */
	public function it_should_create_http_request_with_url_only() {
		$request = new HTTP_Request( 'https://example.com' );

		$this->assertEquals( 'https://example.com', $request->get_url() );
		$this->assertEquals( 'GET', $request->get_method() );
		$this->assertEquals( [], $request->get_request_args() );
	}

	/**
	 * @test
	 */
	public function it_should_create_http_request_with_method() {
		$request = new HTTP_Request( 'https://example.com', [], 'POST' );

		$this->assertEquals( 'https://example.com', $request->get_url() );
		$this->assertEquals( 'POST', $request->get_method() );
		$this->assertEquals( [], $request->get_request_args() );
	}

	/**
	 * @test
	 */
	public function it_should_create_http_request_with_args() {
		$args = [
			'headers' => [ 'Authorization' => 'Bearer token' ],
			'body'    => '{"test": "data"}',
			'timeout' => 60,
		];

		$request = new HTTP_Request( 'https://example.com', $args, 'POST' );

		$this->assertEquals( 'https://example.com', $request->get_url() );
		$this->assertEquals( 'POST', $request->get_method() );
		$this->assertEquals( $args, $request->get_request_args() );
	}

	/**
	 * @test
	 */
	public function it_should_normalize_method_to_uppercase() {
		$request = new HTTP_Request( 'https://example.com', [], 'post' );

		$this->assertEquals( 'POST', $request->get_method() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_empty_url() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'URL is required.' );

		new HTTP_Request( '' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_method() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'HTTP method must be one of: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS' );

		new HTTP_Request( 'https://example.com', [], 'INVALID' );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_non_array_args() {
		$this->expectException( TypeError::class );

		/** @var array $args */
		$args = 'not-an-array';
		new HTTP_Request( 'https://example.com', $args, 'GET' );
	}

	/**
	 * @test
	 */
	public function it_should_process_successful_request() {
		$request = new HTTP_Request( 'https://example.com' );

		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$spy = [];
		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $response ) {
			$spy = [ $url, $args ];
			return $response;
		}, true );

		$request->process();

		$this->assertEquals( 'https://example.com', $spy[0] );
		$this->assertEquals( 'GET', $spy[1]['method'] );
		$this->assertEquals( 3, $spy[1]['timeout'] ); // Default timeout
	}

	/**
	 * @test
	 */
	public function it_should_process_request_with_custom_timeout() {
		$request = new HTTP_Request( 'https://example.com', [ 'timeout' => 60 ] );

		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$spy = [];
		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $response ) {
			$spy = [ $url, $args ];
			return $response;
		}, true );

		$request->process();

		$this->assertEquals( 60, $spy[1]['timeout'] ); // Custom timeout
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_wp_error() {
		$request = new HTTP_Request( 'https://example.com' );

		$error = new WP_Error( 'http_request_failed', 'Connection timeout' );

		$this->set_fn_return( 'wp_remote_request', $error );

		$this->expectException( ShepherdTaskFailWithoutRetryException::class );
		$this->expectExceptionMessage( 'HTTP GET request to https://example.com failed with code: `http_request_failed` and message: `Connection timeout`' );

		$request->process();
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_http_error_status() {
		$request = new HTTP_Request( 'https://example.com' );

		$response = [
			'response' => [
				'code'    => 404,
				'message' => 'Not Found',
			],
			'body'     => 'Page not found',
		];

		$this->set_fn_return( 'wp_remote_request', $response );

		$this->expectException( ShepherdTaskFailWithoutRetryException::class );
		$this->expectExceptionMessage( 'HTTP GET request to https://example.com returned error 404: `Not Found`' );

		$request->process();
	}

	/**
	 * @test
	 */
	public function it_should_throw_retryable_exception_for_5xx_error() {
		$request = new HTTP_Request( 'https://example.com' );

		$response = [
			'response' => [
				'code'    => 500,
				'message' => 'Internal Server Error',
			],
			'body'     => 'Server error',
		];

		$this->set_fn_return( 'wp_remote_request', $response );

		$this->expectException( ShepherdTaskException::class );
		$this->expectExceptionMessage( 'HTTP GET request to https://example.com returned error 500: `Internal Server Error`' );

		$request->process();
	}

	/**
	 * @test
	 */
	public function it_should_have_auth_headers_method() {
		$request = new HTTP_Request( 'https://example.com' );

		$this->assertEquals( [], $request->get_auth_headers() );
	}

	/**
	 * @test
	 */
	public function it_should_have_correct_task_prefix() {
		$request = new HTTP_Request( 'https://example.com' );

		$this->assertEquals( 'shepherd_http_', $request->get_task_prefix() );
	}

	/**
	 * @test
	 */
	public function it_should_have_10_max_retries() {
		$request = new HTTP_Request( 'https://example.com' );

		$this->assertEquals( 10, $request->get_max_retries() );
	}

	/**
	 * @test
	 */
	public function it_should_fire_action_on_successful_request() {
		$request = new HTTP_Request( 'https://example.com' );

		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$this->set_fn_return( 'wp_remote_request', $response );

		$prefix = tests_shepherd_get_hook_prefix();

		$this->assertSame( 0, did_action( 'shepherd_' . $prefix . '_http_request_processed' ) );

		$request->process();

		$this->assertSame( 1, did_action( 'shepherd_' . $prefix . '_http_request_processed' ) );
	}

	/**
	 * @test
	 */
	public function it_should_support_all_http_methods() {
		$methods = [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS' ];

		foreach ( $methods as $method ) {
			$request = new HTTP_Request( 'https://example.com', [], $method );
			$this->assertEquals( $method, $request->get_method() );
		}
	}
}
