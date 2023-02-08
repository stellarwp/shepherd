<?php

namespace StellarWP\Pigeon\Config;

use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Schema\Tables\Entries_Meta;
use StellarWP\Schema\Register;
use StellarWP\Schema\Config;
use StellarWP\DB\DB;

class Database extends DB {

	public function register() {
		Config::set_container( new Container() );
		Config::set_db( __CLASS__ );

		$this->set_tables();
	}

	public function set_tables() {
		Register::table( Entries::class );
		Register::table( Entries_Meta::class );
	}

}