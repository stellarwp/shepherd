<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Tags\Collection;

interface Template_Interface {

	public function register();

	public function render( Collection $tags );

	public function validate();

}