<?php

declare( strict_types=1 );

namespace StellarWP\Shepherd\Tables;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\Shepherd\Log;
use StellarWP\DB\DB;

class Task_Logs_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function it_should_get_logs_by_task_id() {
		$table_name = Task_Logs::table_name();
		$task_id_1 = 1;
		$task_id_2 = 2;

		// Insert some logs
		DB::query( "INSERT INTO {$table_name} (task_id, level, type, entry, date) VALUES ({$task_id_1}, 'info', 'created', 'Log 1', '2023-01-01 10:00:00')" );
		DB::query( "INSERT INTO {$table_name} (task_id, level, type, entry, date) VALUES ({$task_id_1}, 'info', 'started', 'Log 2', '2023-01-01 10:01:00')" );
		DB::query( "INSERT INTO {$table_name} (task_id, level, type, entry, date) VALUES ({$task_id_2}, 'error', 'failed', 'Log 3', '2023-01-01 10:02:00')" );

		$logs = Task_Logs::get_by_task_id( $task_id_1 );

		$this->assertCount( 2, $logs );
		$this->assertInstanceOf( Log::class, $logs[0] );
		$this->assertEquals( 'Log 1', $logs[0]->get_entry() );
		$this->assertEquals( '2023-01-01 10:00:00', $logs[0]->get_date()->format( 'Y-m-d H:i:s' ) );

		$this->assertInstanceOf( Log::class, $logs[1] );
		$this->assertEquals( 'Log 2', $logs[1]->get_entry() );
		$this->assertEquals( '2023-01-01 10:01:00', $logs[1]->get_date()->format( 'Y-m-d H:i:s' ) );

		$this->assertCount( 1, Task_Logs::get_by_task_id( $task_id_2 ) );
		$this->assertCount( 0, Task_Logs::get_by_task_id( 3 ) );
	}
}
