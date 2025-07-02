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
}
