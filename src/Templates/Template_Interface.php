<?php

namespace StellarWP\Pigeon\Templates;

interface Template_Interface {

	public function register();

	public function render();

	public function validate();
}
