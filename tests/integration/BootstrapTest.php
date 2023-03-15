<?php

namespace integration;

use StellarWP\Pigeon\Pigeon;

class BootstrapTest extends \Codeception\TestCase\WPTestCase {

	public function setUp(): void {
		parent::setUp();
		include_once 'ExampleContainer.php';
	}

	public function test_init_returns_null_if_pigeon_not_enabled() {
		$instance = $this->make( Pigeon::class )->init( new ExampleContainer() );
		$this->assertNull( $instance );
	}

	public function test_is_enabled_returns_false_if_pigeon_not_enabled() {
		$this->assertFalse( Pigeon::is_enabled() );
	}

	public function test_is_enabled_returns_true_if_pigeon_enabled() {
		if ( ! defined( 'STELLARWP_PIGEON_ENABLE' ) ) {
			define( 'STELLARWP_PIGEON_ENABLE', true );
		}
		$this->assertTrue( Pigeon::is_enabled() );
	}

	public function test_instance_is_set_on_init_if_pigeon_enabled() {
		if ( ! defined( 'STELLARWP_PIGEON_ENABLE' ) ) {
			define( 'STELLARWP_PIGEON_ENABLE', true );
		}

		$this->expectException( \TypeError::class );
		$this->assertTrue( Pigeon::get_instance() instanceof Pigeon );

		$this->make( Pigeon::class )->init( new ExampleContainer() );
		$this->assertTrue(  Pigeon::get_instance() instanceof Pigeon );
	}


}