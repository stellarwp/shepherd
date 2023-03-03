<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Modules\Module_Interface;

interface Model_Interface {

	public function set_data() :Model_Interface;

	public function validate_dataset() :bool;

}