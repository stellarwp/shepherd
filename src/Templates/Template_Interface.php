<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Model_Interface;

interface Template_Interface {

	public function register();

	public function compose( Model_Interface $entry );

	public function render();
}