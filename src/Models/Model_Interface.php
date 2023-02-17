<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Modules\Module_Interface;

interface Model_Interface {

	public function __construct( Module_Interface $module );

	public function set_data() :void;

	public function validate_dataset() :bool;

}