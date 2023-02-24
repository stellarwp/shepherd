<?php

namespace StellarWP\Pigeon\Tags;

use StellarWP\Pigeon\Models\Entry;

interface Tag_Interface {

	public function register();

	public function get_tag_name();

	public function compose( Entry $entry );

	public function render();
}