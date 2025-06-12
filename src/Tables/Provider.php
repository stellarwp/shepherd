<?php

namespace StellarWP\Pigeon\Tables;

use lucatume\DI52\ServiceProvider;
use StellarWP\Schema\Register;

class Provider extends ServiceProvider {
	public function register() {
		Register::table( Tasks::class );
		Register::table( Entries_Meta::class );
	}
}
