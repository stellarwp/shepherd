<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Psr\Log\LogLevel;
use InvalidArgumentException;
use StellarWP\Pigeon\Contracts\Log_Model;
use StellarWP\Pigeon\Tables\AS_Logs;

class Log_Test extends WPTestCase {
	private function get_log_instance(): Log {
		return new Log();
	}

	/**
	 * @test
	 */
	public function it_should_be_a_log_model(): void {
		$log = $this->get_log_instance();
		$this->assertInstanceOf( Log_Model::class, $log );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_task_id(): void {
		$log = $this->get_log_instance();
		$log->set_task_id( 123 );
		$this->assertEquals( 123, $log->get_task_id() );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_date(): void {
		$log  = $this->get_log_instance();
		$date = new \DateTime();
		$log->set_date( $date );
		$this->assertSame( $date, $log->get_date() );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_level(): void {
		$log = $this->get_log_instance();
		$log->set_level( LogLevel::INFO );
		$this->assertEquals( LogLevel::INFO, $log->get_level() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_level(): void {
		$this->expectException( InvalidArgumentException::class );
		$log = $this->get_log_instance();
		$log->set_level( 'invalid-level' );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_type(): void {
		$log = $this->get_log_instance();
		$log->set_type( 'created' );
		$this->assertEquals( 'created', $log->get_type() );
	}

	/**
	 * @test
	 */
	public function it_should_throw_exception_for_invalid_type(): void {
		$this->expectException( InvalidArgumentException::class );
		$log = $this->get_log_instance();
		$log->set_type( 'invalid-type' );
	}

	/**
	 * @test
	 */
	public function it_should_set_and_get_entry(): void {
		$log = $this->get_log_instance();
		$log->set_entry( '  Log entry.  ' );
		$this->assertEquals( 'Log entry.', $log->get_entry() );
	}

	/**
	 * @test
	 */
	public function it_should_get_table_interface(): void {
		$log = $this->get_log_instance();
		$this->assertInstanceOf( AS_Logs::class, $log->get_table_interface() );
	}

	/**
	 * @test
	 */
	public function it_should_have_all_type_constants_in_valid_types(): void {
		$expected = [
			Log::TYPE_CREATED,
			Log::TYPE_STARTED,
			Log::TYPE_FINISHED,
			Log::TYPE_FAILED,
			Log::TYPE_RESCHEDULED,
			Log::TYPE_CANCELLED,
			Log::TYPE_RETRYING,
		];

		$this->assertEquals( $expected, Log::VALID_TYPES );
	}

	/**
	 * @test
	 */
	public function it_should_convert_to_array(): void {
		$log  = $this->get_log_instance();
		$date = new \DateTime( '2024-01-01 12:00:00' );

		$log->set_id( 999 );
		$log->set_task_id( 123 );
		$log->set_action_id( 456 );
		$log->set_date( $date );
		$log->set_level( LogLevel::ERROR );
		$log->set_type( Log::TYPE_FAILED );
		$log->set_entry( 'Test error message' );

		$array = $log->to_array();

		$this->assertIsArray( $array );
		$this->assertEquals( 999, $array['id'] );
		$this->assertEquals( 123, $array['task_id'] );
		$this->assertEquals( 456, $array['action_id'] );
		$this->assertEquals( $date, $array['date'] );
		$this->assertEquals( LogLevel::ERROR, $array['level'] );
		$this->assertEquals( Log::TYPE_FAILED, $array['type'] );
		$this->assertEquals( 'Test error message', $array['entry'] );
	}
}
