<?php

namespace StellarWP\Pigeon;

class Provider {

	public function register() {
		add_action( 'plugin_activate', [ $this, 'enable_big_sql_selects' ] );
	}

}