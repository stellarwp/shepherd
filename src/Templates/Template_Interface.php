<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\Entry_Interface;

interface Template_Interface {

	public function register();

	public function compose( Entry_Interface $entry );

	public function render();
}