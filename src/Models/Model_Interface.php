<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Modules\Module_Interface;

interface Model_Interface {

	public function __constructor( Module_Interface $module ) :void;

	public function set_data( ...$args ) :void;

	public function validate_dataset() :bool;

}