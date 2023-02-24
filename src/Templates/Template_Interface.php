<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;

interface Template_Interface {

	public function register();

	public function render( Entry $entry );

	public function validate();

}