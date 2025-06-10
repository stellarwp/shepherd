<?php

namespace StellarWP\Pigeon;

use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Schema\Tables\Entries_Meta;
use StellarWP\Schema\Register;
use StellarWP\Schema\Config;
use StellarWP\DB\DB;

class Database extends DB {

	protected $container;

	public function __construct() {
		$this->set_container( Pigeon::get_instance()->get_container() );
	}

	public function register() {

		if ( empty( $this->container  ) ) {
			return;
		}

		Config::set_container( $this->container );
		Config::set_db( __CLASS__ );

		$this->set_tables();
	}

	public function set_tables() {
		Register::table( Entries::class );
		Register::table( Entries_Meta::class );
	}

	public function set_container( $container ) {
		$this->container = $container;
	}

}