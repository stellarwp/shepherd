<?php
declare( strict_types=1 );

namespace StellarWP\Shepherd\Tasks;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Tests\Traits\With_AS_Assertions;
use StellarWP\Shepherd\Tests\Traits\With_Clock_Mock;
use StellarWP\Shepherd\Tests\Traits\With_Log_Snapshot;
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use WP_Error;

use function StellarWP\Shepherd\shepherd;

class HTTP_Request_Test extends WPTestCase {
	use With_AS_Assertions;
	use With_Uopz;
	use With_Clock_Mock;
	use With_Log_Snapshot;

	/**
	 * @before
	 */
	public function setup(): void {
		$this->freeze_time( tests_shepherd_get_dt() );
		shepherd()->bust_runtime_cached_tasks();
	}

	private function get_logger(): Logger {
		return Config::get_container()->get( Logger::class );
	}

	/**
	 * @test
	 */
	public function it_should_dispatch_and_process_http_get_request(): void {
		$spy = [];
		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $response ) {
			$spy[] = [ $url, $args ];
			return $response;
		}, true );

		$shepherd = shepherd();
		$this->assertNull( $shepherd->get_last_scheduled_task_id() );

		$task = new HTTP_Request( 'https://example.com/api/test' );
		$shepherd->dispatch( $task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertIsInt( $last_scheduled_task_id );

		$this->assertTaskHasActionPending( $last_scheduled_task_id );

		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertEquals( 'https://example.com/api/test', $spy[0][0] );
		$this->assertEquals( 'GET', $spy[0][1]['method'] );
		$this->assertEquals( 3, $spy[0][1]['timeout'] );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 3, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'finished', $logs[2]->get_type() );

	}

	/**
	 * @test
	 */
	public function it_should_dispatch_and_process_http_post_request_with_body(): void {
		$spy = [];
		$response = [
			'response' => [
				'code'    => 201,
				'message' => 'Created',
			],
			'body'     => '{"id": 123}',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $response ) {
			$spy[] = [ $url, $args ];
			return $response;
		}, true );

		$shepherd = shepherd();

		$request_args = [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer token123',
			],
			'body'    => '{"name": "Test Item", "value": 42}',
			'timeout' => 60,
		];

		$task = new HTTP_Request( 'https://api.example.com/items', $request_args, 'POST' );
		$shepherd->dispatch( $task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertEquals( 'https://api.example.com/items', $spy[0][0] );
		$this->assertEquals( 'POST', $spy[0][1]['method'] );
		$this->assertEquals( 60, $spy[0][1]['timeout'] );
		$this->assertEquals( 'application/json', $spy[0][1]['headers']['Content-Type'] );
		$this->assertEquals( 'Bearer token123', $spy[0][1]['headers']['Authorization'] );
		$this->assertEquals( '{"name": "Test Item", "value": 42}', $spy[0][1]['body'] );
	}

	/**
	 * @test
	 */
	public function it_should_fail_immediately_on_wp_error(): void {
		$spy = [];
		$error = new WP_Error( 'http_request_failed', 'Connection timeout' );

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $error ) {
			$spy[] = [ $url, $args ];
			return $error;
		}, true );

		$shepherd = shepherd();

		$task = new HTTP_Request( 'https://flaky-api.example.com' );
		$shepherd->dispatch( $task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskHasActionPending( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		// Task fails immediately without retry
		$this->assertTaskExecutesFails( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertEquals( 'https://flaky-api.example.com', $spy[0][0] );
		$this->assertEquals( 'GET', $spy[0][1]['method'] );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 3, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'failed', $logs[2]->get_type() );

	}

	/**
	 * @test
	 */
	public function it_should_retry_on_http_error_status(): void {
		$spy = [];
		$error_response = [
			'response' => [
				'code'    => 500,
				'message' => 'Internal Server Error',
			],
			'body'     => 'Server is temporarily unavailable',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $error_response ) {
			$spy[] = [ $url, $args ];
			return $error_response;
		}, true );

		$shepherd = shepherd();

		$task = new HTTP_Request( 'https://unstable-api.example.com' );
		$shepherd->dispatch( $task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		// First attempt fails and reschedules
		$this->assertTaskExecutesFailsAndReschedules( $last_scheduled_task_id );

		// Change response to success for retry
		$success_response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"status": "recovered"}',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $success_response ) {
			$spy[] = [ $url, $args ];
			return $success_response;
		}, true );

		// Second attempt succeeds
		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 2, $spy );
		$this->assertEquals( 'https://unstable-api.example.com', $spy[0][0] );
		$this->assertEquals( 'https://unstable-api.example.com', $spy[1][0] );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 5, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'rescheduled', $logs[2]->get_type() );
		$this->assertSame( 'retrying', $logs[3]->get_type() );
		$this->assertSame( 'finished', $logs[4]->get_type() );

	}

	/**
	 * @test
	 */
	public function it_should_fail_immediately_on_4xx_error(): void {
		$spy = [];
		$error_response = [
			'response' => [
				'code'    => 404,
				'message' => 'Not Found',
			],
			'body'     => 'Page not found',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $error_response ) {
			$spy[] = [ $url, $args ];
			return $error_response;
		}, true );

		$shepherd = shepherd();

		$task = new HTTP_Request( 'https://missing-api.example.com' );
		$shepherd->dispatch( $task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertTaskHasActionPending( $last_scheduled_task_id );
		$this->assertTaskIsScheduledForExecutionAt( $last_scheduled_task_id, time() );

		// Task fails immediately without retry for 4xx errors
		$this->assertTaskExecutesFails( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertEquals( 'https://missing-api.example.com', $spy[0][0] );
		$this->assertEquals( 'GET', $spy[0][1]['method'] );

		$logs = $this->get_logger()->retrieve_logs( $last_scheduled_task_id );
		$this->assertCount( 3, $logs );
		$this->assertSame( 'created', $logs[0]->get_type() );
		$this->assertSame( 'started', $logs[1]->get_type() );
		$this->assertSame( 'failed', $logs[2]->get_type() );
	}

	/**
	 * @test
	 */
	public function it_should_fire_action_on_successful_request(): void {
		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( $response ) {
			return $response;
		}, true );

		$action_fired = false;
		/** @var Task|null $action_task */
		$action_task = null;
		$action_response = null;

		$hook_name = 'shepherd_' . tests_shepherd_get_hook_prefix() . '_http_request_processed';
		add_action( $hook_name, function ( $task, $resp ) use ( &$action_fired, &$action_task, &$action_response ) {
			$action_fired = true;
			$action_task = $task;
			$action_response = $resp;
		}, 10, 2 );

		$shepherd = shepherd();

		$task = new HTTP_Request( 'https://webhook.example.com/notify' );
		$this->assertEquals( 0, $task->get_id() );
		$shepherd->dispatch( $task );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();

		$this->assertEquals( $last_scheduled_task_id, $task->get_id() );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertTrue( $action_fired );
		$this->assertInstanceOf( HTTP_Request::class, $action_task );
		$this->assertSame( $action_task->get_id(), $task->get_id() );
		$this->assertEquals( $response, $action_response );
	}

	/**
	 * @test
	 */
	public function it_should_not_schedule_same_request_twice(): void {
		$spy = [];
		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $response ) {
			$spy[] = [ $url, $args ];
			return $response;
		}, true );

		$shepherd = shepherd();

		$task = new HTTP_Request( 'https://api.example.com/webhook', [ 'body' => '{"event": "test"}' ], 'POST' );

		$hook_name = 'shepherd_' . tests_shepherd_get_hook_prefix() . '_task_already_scheduled';

		$this->assertSame( 0, did_action( $hook_name ) );
		$shepherd->dispatch( $task );
		$this->assertSame( 0, did_action( $hook_name ) );

		$last_scheduled_task_id = $shepherd->get_last_scheduled_task_id();
		$this->assertIsInt( $last_scheduled_task_id );

		// Try to dispatch same task again
		$shepherd->dispatch( $task );
		$this->assertSame( 1, did_action( $hook_name ) );
		$this->assertEquals( $shepherd->get_last_scheduled_task_id(), $last_scheduled_task_id );

		// Try with identical task instance
		$shepherd->dispatch( new HTTP_Request( 'https://api.example.com/webhook', [ 'body' => '{"event": "test"}' ], 'POST' ) );
		$this->assertSame( 2, did_action( $hook_name ) );
		$this->assertEquals( $shepherd->get_last_scheduled_task_id(), $last_scheduled_task_id );

		$this->assertTaskExecutesWithoutErrors( $last_scheduled_task_id );

		$this->assertCount( 1, $spy );
		$this->assertEquals( 'https://api.example.com/webhook', $spy[0][0] );
		$this->assertEquals( 'POST', $spy[0][1]['method'] );
		$this->assertEquals( '{"event": "test"}', $spy[0][1]['body'] );
	}

	/**
	 * @test
	 */
	public function it_should_schedule_different_requests_separately(): void {
		$spy = [];
		$response = [
			'response' => [
				'code'    => 200,
				'message' => 'OK',
			],
			'body'     => '{"success": true}',
		];

		$this->set_fn_return( 'wp_remote_request', function ( $url, $args ) use ( &$spy, $response ) {
			$spy[] = [ $url, $args ];
			return $response;
		}, true );

		$shepherd = shepherd();

		$task1 = new HTTP_Request( 'https://api1.example.com/endpoint' );
		$shepherd->dispatch( $task1 );
		$task1_id = $shepherd->get_last_scheduled_task_id();
		$this->assertIsInt( $task1_id );

		$task2 = new HTTP_Request( 'https://api2.example.com/endpoint' );
		$shepherd->dispatch( $task2 );
		$task2_id = $shepherd->get_last_scheduled_task_id();
		$this->assertIsInt( $task2_id );

		$this->assertNotEquals( $task1_id, $task2_id );

		$hook_name = 'shepherd_' . tests_shepherd_get_hook_prefix() . '_task_already_scheduled';
		$this->assertSame( 0, did_action( $hook_name ) );

		$this->assertTaskExecutesWithoutErrors( $task1_id );
		$this->assertTaskExecutesWithoutErrors( $task2_id );

		$this->assertCount( 2, $spy );
		$this->assertEquals( 'https://api1.example.com/endpoint', $spy[0][0] );
		$this->assertEquals( 'https://api2.example.com/endpoint', $spy[1][0] );
	}
}
