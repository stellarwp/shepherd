<?php

declare( strict_types=1 );

namespace StellarWP\Pigeon\Tables;

use lucatume\WPBrowser\TestCase\WPTestCase;
use StellarWP\DB\DB;
use StellarWP\Pigeon\Provider;

class Tasks_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function the_table_name_should_be_using_the_prefix(): void {
		$name = Tasks::base_table_name();
		$this->assertStringContainsString( Provider::get_hook_prefix(), $name );

		$query = DB::prepare( 'SHOW TABLES LIKE %s', DB::prefix( $name ) );
		$tables = DB::get_results( $query );

		$this->assertNotEmpty( $tables );
		$this->assertCount( 1, $tables );
	}
}