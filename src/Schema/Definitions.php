<?php

namespace StellarWP\Pigeon\Schema;

use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Schema\Tables\EntriesMeta;
use StellarWP\Schema\Register;

class Definitions {

	public function register() {
		Register::table( Entries::class );
		Register::table( EntriesMeta::class );
	}
}