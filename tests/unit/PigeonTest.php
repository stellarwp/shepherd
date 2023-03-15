<?php

namespace StellarWP\Pigeon;

class PigeonTest extends \Codeception\Test\Unit {

	public function test_pigeon_is_enabled() {
		$this->assertFalse( Pigeon::is_enabled() );

		define( 'STELLARWP_PIGEON_ENABLE', true );

		$this->assertTrue( Pigeon::is_enabled() );
	}

	public function test_instance_setter_getter() {
		$this->expectException( \TypeError::class );
		$this->assertTrue( Pigeon::get_instance() instanceof Pigeon );

		Pigeon::set_instance( new Pigeon() );
		$this->assertTrue( Pigeon::get_instance() instanceof Pigeon );
	}
}
