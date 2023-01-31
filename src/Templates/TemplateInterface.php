<?php

namespace StellarWP\Pigeon\Templates;

use StellarWP\Pigeon\Entry\EntryInterface;

interface TemplateInterface {

	public function register();

	public function compose( EntryInterface $entry );

	public function render();
}