<?php

namespace StellarWP\Pigeon\Models;

interface Model_Interface {

	public function set_data(): Model_Interface;

	public function validate_dataset(): bool;
}
